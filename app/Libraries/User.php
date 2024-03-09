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
    public function getUserInfo($id): array
    {
        $db = Database::connect();
        try {
            $query = $db->table('users')->where('id', $id)->get();
            if ($query->getNumRows() > 0) {
                return $query->getRowArray();
            } else {
                return [];
            }
        } catch (DatabaseException $e) {
            return ['error' => 'Ошибка получения информации о пользователе: ' . $id . ' (' . $e->getMessage() . ')'];
        } finally {
            $db->close();
        }
    }

    /*
     * Регистрация пользователя
     */
    public function userRegistration($UserInfo): array
    {
        $db = Database::connect();
        try {
            $UserInfo['b_date'] = strlen($UserInfo['b_date']) > 5 ? date('Y-m-d', strtotime($UserInfo['b_date'])) : '0000-00-00';
            $UserInfo['age'] = $UserInfo['b_date'] == '0000-00-00' ? 0 : $this->getUserAge($UserInfo['b_date']);
            $UserInfo['create_at'] = date('Y-m-d H:i:s');
            $UserInfo['modify_at'] = date('Y-m-d H:i:s');
            $UserInfo['ip'] = $this->getIPAddress();
            $UserInfo['date'] = date('Y-m-d');
            $db->transException(true)->transStart();
            $db->table('users')->insert($UserInfo);
            $cache = Services::cache();
            if (!$cache instanceof CacheInterface) {
                throw new RuntimeException('Сервис кэша недоступен');
            }
            if (!$cache->save('UserInfo' . $UserInfo['id'], $UserInfo, 3600)) {
                throw new RuntimeException('Ошибка при сохранении данных в кэше');
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
     * Обновление данных пользователя
     */
    public function updateUser($UserInfo, $data): array
    {
        foreach ($data as $key => $val) {
            $UserInfo[$key] = $val;
        }
        $db = Database::connect();
        try {
            $db->transException(true)->transStart();
            $db->table('users')->where('id', $UserInfo['id'])->update($data);
            $cache = Services::cache();
            if (!$cache instanceof CacheInterface) {
                throw new RuntimeException('Сервис кэша недоступен');
            }
            if (!$cache->save('UserInfo' . $UserInfo['id'], $UserInfo, 3600)) {
                throw new RuntimeException('Ошибка при сохранении данных в кэше');
            }
            $db->transComplete();
            return $UserInfo;
        } catch (DatabaseException $e) {
            $db->transRollback();
            return ['error' => 'Ошибка обновления данных пользователя в БД: ' . $UserInfo['id'] . ' (' . $e->getMessage() . ')'];
        } catch (RuntimeException $e) {
            $db->transRollback();
            return ['error' => 'Ошибка обновления данных пользователя в кэше: ' . $UserInfo['id'] . ' (' . $e->getMessage() . ')'];
        } finally {
            $db->close();
        }
    }

}