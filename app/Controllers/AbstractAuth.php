<?php

namespace App\Controllers;

use App\Libraries\App;
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
     * Ключ ошибки
     */
    protected string $errorKey = 'auth_error';

    /*
     * Конструктор
     */
    public function __construct()
    {
        $this->config = config('Personal/Settings');
        App::blockAppByParam($this->config->app_stop);
        App::blockAppByIp(User::getIPAddress(),$this->config->blocked_ips);
        $request = service('request');
        $this->params = $request->getGet();
        $this->ClassName = $this->config->soc_class_name;
        $this->SocClass = new $this->ClassName($this->config);
        $this->UserInfo = [];
    }

    /*
     * Проверка подписи запроса
     */
    protected function checkSignature(): array
    {
        $this->params = $this->SocClass->verifyKey($this->params);
        if (isset($this->params['error'])) {
            App::sendResponseError($this->params['error'], $this->errorKey, 401);
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
            App::sendResponseError($this->UserInfo['error'],$this->errorKey);
        }
        return $this->UserInfo;
    }

    /*
     * Получение информации о пользователе из БД. Регистрация и авторизация пользователя
     */
    protected function getUserInfoFromDb(): array
    {
        $User = new User($this->UserInfo['id'], $this->params['access_token'], $this->config, ['update_session' => true]);
        $UserInfoFromDb = $User->getUserInfo();
        //Авторизация
        if (isset($UserInfoFromDb['id'])) {
            $data = [
                'modify_at' => date('Y-m-d H:i:s'),
                'date' => date('Y-m-d'),
                'ip' => User::getIPAddress(),
                'age' => $UserInfoFromDb['b_date'] == '0000-00-00' ? 0 : $User->getUserAge($UserInfoFromDb['b_date'])
            ];
            $UserInfoFromDb = $User->updateUser($UserInfoFromDb, $data);
        } //Регистрация
        elseif (!isset($UserInfoFromDb['error'])) {
            $UserInfoFromDb = $User->userRegistration($this->UserInfo);
        }
        if (isset($UserInfoFromDb['error'])) {
            App::sendResponseError($UserInfoFromDb['error'],$this->errorKey);
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
            'configData' => (new ConfigData())->getConfigData(),
            'error' => '',
            'error_key' => ''
        );
    }

}