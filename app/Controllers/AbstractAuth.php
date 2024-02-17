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
     * Класс для работы с пользователем
     */
    protected User $User;

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
        $this->User = new User();
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
        $Response->send();
        exit();
    }

    /*
     * Проверка подписи запроса
     */
    protected function checkSignature(): array
    {
        $this->params = $this->SocClass->verifyKey($this->params);
        if (count($this->params) === 0) {
            $this->sendResponseError('Неверная подпись запроса');
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
        $UserInfoFromDb = $this->User->getUserInfo($this->UserInfo['id']);
        if (isset($UserInfoFromDb['error'])) {
            $this->sendResponseError($UserInfoFromDb['error']);
        }
        /*
         * Регистрация пользователя
         */
        if (!isset($UserInfoFromDb['id'])) {
            $UserInfoFromDb = $this->User->userRegistration($this->UserInfo);
            if (isset($UserInfoFromDb['error'])) {
                $this->sendResponseError($UserInfoFromDb['error']);
            }
        }
        return $UserInfoFromDb;
    }

}