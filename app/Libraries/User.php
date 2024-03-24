<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use Config\Database;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Services;
use DateTime;
use Exception;
use RuntimeException;

/**
 * Класс для работы с данными пользователя
 */
class User
{
    /*
     * Идентификатор пользователя
     */
    private int $userId;

    /*
     * Имя пользовательской таблицы
     */
    private string $tableName = 'users';

    /*
     * Имя пользовательской таблицы сессий
     */
    private string $sessionTableName = 'users_sessions';

    /*
     * Имя ячейки пользователя для кэша
     */
    private string $userCacheItemName;

    /*
     * Имя ячейки пользовательской сессии для кэша
     */
    private string $userSessionCacheItemName;

    /*
     * Число секунд для сохранения данных в кэше
     */
    private int $userCacheItemSeconds;

    /*
     * Access Token
     */
    private string $accessToken;

    /*
     * Опции для вставки/обновления данных
     */
    private array $options = [
        'update_session' => false
    ];

    /*
     * Конструктор
     */
    public function __construct($userId, $accessToken, $config, $options = null)
    {
        $this->userId = $userId;
        $this->accessToken = $accessToken;
        $this->tableName = $this->tableName . substr((string)$userId, -1);
        $this->userCacheItemName = 'UserInfo_' . $config->app_name . '_' . $userId;
        $this->userSessionCacheItemName = 'UserSession_' . $config->app_name . '_' . $this->accessToken;
        $this->userCacheItemSeconds = $config->user_cache_item_seconds;
        $this->options = empty($options) ? $this->options : $options;
        $this->options['use_cache'] = $config->use_cache;
    }

    /*
     * Определение IP адреса пользователя
     */
    public static function getIPAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        return $ipAddress;
    }

    /*
     * Определение возраста пользователя
     */
    public function getUserAge($dateOfBirth): int
    {
        try {
            $currentDate = new DateTime();
            $birthdate = new DateTime($dateOfBirth);
            return $birthdate->diff($currentDate)->y;
        } catch (Exception $e) {
            return 0;
        }
    }

    /*
     * Получение информации о пользователе
     */
    public function getUserInfo(): array
    {
        $db = Database::connect();
        try {
            $query = $db->table($this->tableName)->where('id', $this->userId)->get();
            if ($query->getNumRows() > 0) {
                return $query->getRowArray();
            } else {
                return [];
            }
        } catch (DatabaseException $e) {
            return ['error' => 'Ошибка получения информации о пользователе: ' . $this->userId . ' (' . $e->getMessage() . ')'];
        } finally {
            $db->close();
        }
    }

    /*
     * Подготовка данных для изменения в БД для пользователя
     */
    private function prepareProgressDbUserData($UserInfo, $data, $db): void
    {
        //Регистрация
        if (empty($data)) {
            $db->table($this->tableName)->insert($UserInfo);
        } //Обновление данных
        else {
            $db->table($this->tableName)->where('id', $UserInfo['id'])->update($data);
        }
    }

    /*
     * Подготовка данных для изменения в БД для сессии пользователя
     */
    private function prepareProgressDbUserSessionData($db): void
    {
        if ($this->options['update_session']) {
            $db->query("REPLACE INTO `" . $this->sessionTableName . "` (`id`, `access_token`) VALUES (?, ?)", [$this->userId, $this->accessToken]);
        }
    }

    /*
     * Подготовка данных для изменения в кэше
     */
    private function prepareProgressCacheUserData($UserInfo): void
    {
        //Если используем кэш
        if ($this->options['use_cache']) {
            $cache = Services::cache();
            if (!$cache instanceof CacheInterface) {
                throw new RuntimeException('Сервис кэша недоступен');
            }
            if (!$cache->save($this->userCacheItemName, $UserInfo, $this->userCacheItemSeconds)) {
                throw new RuntimeException('Ошибка при сохранении данных в кэше: ' . $this->userCacheItemName);
            }
            if ($this->options['update_session']) {
                if (!$cache->save($this->userSessionCacheItemName, $UserInfo['id'], $this->userCacheItemSeconds)) {
                    throw new RuntimeException('Ошибка при сохранении данных в кэше: ' . $this->userSessionCacheItemName);
                }
            }
        }
    }

    /*
     * Изменение данных пользователя
     */
    private function progressUserData($UserInfo, $data = null)
    {
        $db = Database::connect();
        try {
            $db->transException(true)->transStart();
            $this->prepareProgressDbUserData($UserInfo, $data, $db);
            $this->prepareProgressDbUserSessionData($db);
            $this->prepareProgressCacheUserData($UserInfo);
            $db->transComplete();
            return $UserInfo;
        } catch (DatabaseException $e) {
            $db->transRollback();
            return ['error' => 'Ошибка регистрации пользователя в БД: ' . $UserInfo['id'] . ' (' . $e->getMessage() . ')'];
        } catch (RuntimeException $e) {
            $db->transRollback();
            return ['error' => 'Ошибка регистрации пользователя в кэше: ' . $UserInfo['id'] . ' (' . $e->getMessage() . ')'];
        } finally {
            $db->close();
        }
    }

    /*
     * Подготовка данных для вставки
     */
    private function prepareInsertData($UserInfo)
    {
        $UserInfo['b_date'] = strlen($UserInfo['b_date']) > 5 ? date('Y-m-d', strtotime($UserInfo['b_date'])) : '0000-00-00';
        $UserInfo['age'] = $UserInfo['b_date'] == '0000-00-00' ? 0 : $this->getUserAge($UserInfo['b_date']);
        $UserInfo['create_at'] = date('Y-m-d H:i:s');
        $UserInfo['modify_at'] = date('Y-m-d H:i:s');
        $UserInfo['ip'] = self::getIPAddress();
        $UserInfo['date'] = date('Y-m-d');
        return $UserInfo;
    }

    /*
     * Регистрация пользователя
     */
    public function userRegistration($UserInfo): array
    {
        return $this->progressUserData($this->prepareInsertData($UserInfo));
    }

    /*
     * Подготовка данных для обновления
     */
    private function prepareUpdateData($UserInfo, $data): array
    {
        if ($this->options['use_cache']) {
            foreach ($data as $key => $val) {
                $UserInfo[$key] = $val;
            }
        }
        return $UserInfo;
    }

    /*
     * Обновление данных пользователя
     */
    public function updateUser($UserInfo, $data): array
    {
        return $this->progressUserData($this->prepareUpdateData($UserInfo, $data), $data);
    }

}