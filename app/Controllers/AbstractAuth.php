<?php

namespace App\Controllers;

use App\Libraries\ConfigData;
use App\Libraries\User;
use App\Libraries\WorldData;
use CodeIgniter\RESTful\ResourceController;

/*
 * Авторизация в приложении
 */

abstract class AbstractAuth extends ResourceController
{

    /*
     * Параметры отправляемые социальной сетью
     */
    protected $params = [];

    /*
     * Персональные настройки приложения
     */
    protected $config;

    /*
     * Имя класса приложения
     */
    private $ClassName;

    /*
     * Класс для работы с социальной сетью
     */
    private $SocClass;

    /*
     * Информация о пользователе
     */
    protected array $UserInfo;

    /*
     * Конструктор
     */
    public function __construct()
    {
        $this->config = config('Personal/Settings');
        $request = service('request');
        $this->params = $request->getGet();
        $this->ClassName = $this->config->soc_class_name;
        $this->SocClass = new $this->ClassName($this->config);
        $this->UserInfo = [];
    }

    /*
     * Прекращение работы запроса с выводом ошибки
     */
    private function sendResponseError($error)
    {
        $Response = service('response');
        $Response->setStatusCode(403);
        $Response->setContentType('application/json');
        $Response->setJSON([
            'error' => $error,
            'error_key' => 'auth_error'
        ]);
        log_message('error', $error);
        $Response->send();
        exit();
    }

    /*
     * Проверка подписи запроса
     */
    protected function checkSignature(): array
    {
        $this->params = $this->SocClass->verifyKey($this->params);
        if (isset($this->params['error'])) {
            $this->sendResponseError($this->params['error']);
        }
        return $this->params;
    }

    /*
     * Получение информации о пользователе
     */
    protected function getUserInfo(): array
    {
        $this->UserInfo = $this->SocClass->getUserInfo($this->params);
        if (isset($this->UserInfo['error'])) {
            $this->sendResponseError($this->UserInfo['error']);
        }
        return $this->UserInfo;
    }

    /*
     * Получение информации о пользователе из БД. Регистрация и авторизация пользователя
     */
    protected function getUserInfoFromDb(): array
    {
        $User = new User($this->UserInfo['id'], $this->params['access_token'], $this->config);
        $UserInfoFromDb = $User->getUserInfo();
        //Авторизация
        if (isset($UserInfoFromDb['id'])) {
            $data = [
                'modify_at' => date('Y-m-d H:i:s'),
                'date' => date('Y-m-d'),
                'ip' => $User->getIPAddress(),
                'age' => $UserInfoFromDb['b_date'] == '0000-00-00' ? 0 : $User->getUserAge($UserInfoFromDb['b_date'])
            ];
            $UserInfoFromDb = $User->updateUser($UserInfoFromDb, 1, $data);
        } //Регистрация
        elseif (!isset($UserInfoFromDb['error'])) {
            $UserInfoFromDb = $User->userRegistration($this->UserInfo, 1);
        }
        if (isset($UserInfoFromDb['error'])) {
            $this->sendResponseError($UserInfoFromDb['error']);
        }
        return $this->UserInfo = $UserInfoFromDb;
    }

    /*
     * Подготовка ответа для клиента
     */
    protected function setClientData(): array
    {
        return array(
            'playerData' => $this->UserInfo,
            'worldData' => (new WorldData())->getWorldData(),
            'configData' => (new ConfigData())->getConfigData()
        );
    }

}