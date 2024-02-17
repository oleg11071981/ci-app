<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;
use Exception;

/**
 * Класс для работы с данными пользователя
 */
class User
{
    /*
     * Соединение с БД
     */
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /*
     * Определение IP адреса пользователя
     */
    private function getIPAddress()
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
    private function getUserAge($dateOfBirth): int
    {
        $currentDate = date('Y-m-d');
        $birthdate = new DateTime($dateOfBirth);
        $today = new DateTime($currentDate);
        return $birthdate->diff($today)->y;
    }

    /*
     * Получение информации о пользователе
     */
    public function getUserInfo($id): array
    {
        try {
            $query = $this->db->table('users')->where('id', $id)->get();
            if ($query->getNumRows() > 0) {
                return $query->getRowArray();
            } else {
                return [];
            }
        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        } finally {
            $this->db->close();
        }
    }

    /*
     * Регистрация пользователя
     */
    public function userRegistration($UserInfo): array
    {
        try {
            $UserInfo['b_date'] = date('Y-m-d',strtotime($UserInfo['b_date']));
            $UserInfo['create_at'] = date('Y-m-d H:i:s');
            $UserInfo['modify_at'] = date('Y-m-d H:i:s');
            $UserInfo['age'] = $this->getUserAge($UserInfo['b_date']);
            $UserInfo['ip'] = $this->getIPAddress();
            $UserInfo['date'] = date('Y-m-d');
            $this->db->table('users')->insert($UserInfo);
            return $UserInfo;
        } catch (Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        } finally {
            $this->db->close();
        }
    }

    /*
     * Деструктор
     */
    public function __destruct()
    {
        $this->db->close();
    }

}