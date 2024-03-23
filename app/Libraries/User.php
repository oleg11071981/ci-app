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
    private int $userCacheItemSeconds = 3600;

    /*
     * Access Token
     */
    private string $accessToken;

    /*
     * Конструктор
     */
    public function __construct($userId, $params, $config)
    {
        $this->userId = $userId;
        $this->tableName = $this->tableName . substr((string)$userId, -1);
        $this->userCacheItemName = 'UserInfo_' . $config->app_name . '_' . $userId;
        $this->userSessionCacheItemName = 'UserSession_' . $config->app_name . '_' . $params['access_token'];
        $this->accessToken = $params['access_token'];
    }

    /*
     * Определение IP адреса пользователя
     */
    public function getIPAddress()
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
     * Изменение данных пользователя
     */
    private function progressUserData($UserInfo, $updateSession = 0, $data = null)
    {
        $db = Database::connect();
        try {
            $db->transException(true)->transStart();
            //Регистрация
            if (empty($data)) {
                $db->table($this->tableName)->insert($UserInfo);
            } //Обновление данных
            else {
                $db->table($this->tableName)->where('id', $UserInfo['id'])->update($data);
            }
            $cache = Services::cache();
            if (!$cache instanceof CacheInterface) {
                throw new RuntimeException('Сервис кэша недоступен');
            }
            if (!$cache->save($this->userCacheItemName, $UserInfo, $this->userCacheItemSeconds)) {
                throw new RuntimeException('Ошибка при сохранении данных в кэше: ' . $this->userCacheItemName);
            }
            //Если требуется обновить сессию
            if ($updateSession == 1) {
                $db->query("REPLACE INTO `" . $this->sessionTableName . "` (`id`, `access_token`) VALUES (?, ?)", [$UserInfo['id'], $this->accessToken]);
                if (!$cache->save($this->userSessionCacheItemName, $UserInfo['id'], $this->userCacheItemSeconds)) {
                    throw new RuntimeException('Ошибка при сохранении данных в кэше: ' . $this->userSessionCacheItemName);
                }
            }
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
     * Регистрация пользователя
     */
    public function userRegistration($UserInfo, $updateSession): array
    {
        $UserInfo['b_date'] = strlen($UserInfo['b_date']) > 5 ? date('Y-m-d', strtotime($UserInfo['b_date'])) : '0000-00-00';
        $UserInfo['age'] = $UserInfo['b_date'] == '0000-00-00' ? 0 : $this->getUserAge($UserInfo['b_date']);
        $UserInfo['create_at'] = date('Y-m-d H:i:s');
        $UserInfo['modify_at'] = date('Y-m-d H:i:s');
        $UserInfo['ip'] = $this->getIPAddress();
        $UserInfo['date'] = date('Y-m-d');
        return $this->progressUserData($UserInfo, $updateSession);
    }

    /*
     * Обновление данных пользователя
     */
    public function updateUser($UserInfo, $updateSession, $data): array
    {
        foreach ($data as $key => $val) {
            $UserInfo[$key] = $val;
        }
        return $this->progressUserData($UserInfo, $updateSession, $data);
    }

}