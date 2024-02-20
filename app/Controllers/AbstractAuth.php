<?php

namespace App\Controllers;

use App\Libraries\User;
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
        log_message('error',$error);
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
        $User = new User();
        $UserInfoFromDb = $User->getUserInfo($this->UserInfo['id']);
        if (!isset($UserInfoFromDb['id']) && !isset($UserInfoFromDb['error']))
        {
            //Регистрируем пользователя
            $UserInfoFromDb = $User->userRegistration($this->UserInfo);
        }
        if (isset($UserInfoFromDb['error'])) {
            $this->sendResponseError($UserInfoFromDb['error']);
        }
        return $UserInfoFromDb;
    }

}