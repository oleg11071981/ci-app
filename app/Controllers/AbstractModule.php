<?php

namespace App\Controllers;

use App\Libraries\App;
use App\Libraries\User;
use CodeIgniter\RESTful\ResourceController;

/**
 * Базовый контроллер модуля
 */
class AbstractModule extends ResourceController
{

    /*
     * Параметры отправляемые клиентом
     */
    protected $params = [];

    /*
     * Обязательные параметры
     */
    protected array $requiredParams = ['access_token', 'id', 'version'];

    /*
     * Персональные настройки приложения
     */
    protected $config;

    /*
     * Общие настройки приложений
     */
    protected $generalConfig;

    /*
     * Информация о пользователе
     */
    protected array $userInfo;

    /*
     * Класс для работы с пользователем
     */
    protected User $User;

    /*
     * Данные для клиента
     */
    protected array $clientData = [
        'error' => '',
        'error_key' => ''
    ];

    /*
     * Ключ ошибки
     */
    protected string $errorKey = 'general_error';

    public function __construct()
    {
        $this->config = config('Personal/Settings');
        $this->generalConfig = config('General/Settings');
        App::blockAppByParam($this->config->app_stop);
        App::blockAppByIp(User::getIPAddress(), $this->config->blocked_ips);
        $request = service('request');
        $this->params = $request->getGet();
        App::checkParams($this->requiredParams, $this->params);
        App::checkConfigVersion($this->params['version'], $this->generalConfig->configVersion);
        $this->User = new User($this->params['id'], $this->params['access_token'], $this->config);
        $this->getUserData();
    }

    /*
     * Получение информации о пользователе
     */
    private function getUserData(): void
    {
        $this->userInfo = $this->User->checkUserAuth();
        if (isset($this->userInfo['error'])) {
            App::sendResponseError($this->userInfo['error'], 'auth_error');
        }
    }

    /*
     * Обновление данных пользователя
     */
    protected function changeUserData($data,$sqlInfo=[]): void
    {
        $this->userInfo = $this->User->updateUser($this->userInfo, $data, $sqlInfo);
        if (isset($this->userInfo['error'])) {
            App::sendResponseError($this->userInfo['error'], $this->errorKey);
        }
    }

}