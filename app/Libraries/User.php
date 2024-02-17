<?php

namespace App\Libraries;

/**
 * Класс для работы с данными пользователя
 */
class User
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /*
     * Получение информации о пользователе
     */
    public function getUserInfo($id)
    {
        try {
            $query = $this->db->table('users')->where('id', $id)->get();
            if ($query->getNumRows() > 0) {
                return $query->getRowArray();
            } else {
                return [];
            }
        } catch (\Exception $e) {
            return ['error' => 'Database error: ' . $e->getMessage()];
        } finally {
            $this->db->close();
        }
    }

    /*
     * Регистрация пользователя
     */
    public function userRegistration($UserInfo)
    {
        try {
            $UserInfo['create_at'] = date('Y-m-d H:i:s');
            $UserInfo['modify_at'] = date('Y-m-d H:i:s');
            $this->db->table('users')->insert($UserInfo);
            return $UserInfo;
        } catch (\Exception $e) {
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
        if ($this->db !== null) {
            $this->db->close();
        }
    }

}