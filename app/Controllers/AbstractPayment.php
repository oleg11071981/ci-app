<?php

namespace App\Controllers;

use App\Libraries\Response;
use App\Libraries\User;
use CodeIgniter\RESTful\ResourceController;

/**
 * Базовый контроллер платёжки
 */
class AbstractPayment extends ResourceController
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
     * Персональные настройки платёжки приложения
     */
    protected $paymentsConfig;

    /*
     * Имя класса приложения
     */
    private $ClassName;

    /*
     * Класс для работы с социальной сетью
     */
    private $SocClass;

    /*
     * Идентификатор пользователя
     */
    protected int $userId;

    /*
     * Access Token
     */
    protected string $accessToken;

    /*
     * Информация о пользователе
     */
    protected array $userInfo;

    /*
     * Конструктор
     */
    public function __construct()
    {
        $this->config = config('Personal/Settings');
        $this->paymentsConfig = config('Personal/Payments');
        $request = service('request');
        $this->params = $request->getPost();
        $this->ClassName = $this->config->soc_class_name;
        $this->SocClass = new $this->ClassName($this->config);
    }

    /*
     * Проверка подписи входящих параметров
     */
    protected function checkSignature()
    {
        $this->params = $this->SocClass->checkSignature($this->params, $this->paymentsConfig->payments_params);
        if (isset($this->params['error'])) {
            Response::sendResponse($this->params, 200, $this->params['error']['error_msg']);
        }
        return $this->params;
    }

    /*
     * Получение информации о продукте
     */
    protected function checkProduct()
    {
        $this->params = $this->SocClass->checkProduct($this->params, $this->paymentsConfig->available_products);
        if (isset($this->params['error'])) {
            Response::sendResponse($this->params, 200, $this->params['error']['error_msg']);
        } elseif (isset($this->params['response'])) {
            Response::sendResponse($this->params);
        }
        return $this->params;
    }

    /*
     * Проверка наличия платежа
     */
    protected function checkPayment(): void
    {
        $checkPayment = $this->SocClass->checkPayment($this->params, $this->paymentsConfig->payment_table);
        if (isset($checkPayment['error'])) {
            Response::sendResponse($checkPayment, 200, $this->params['error']['error_msg']);
        }
    }

    /*
     * Проверка наличия информации о пользователе
     */
    protected function checkUserInfo()
    {
        $this->userId = (int)$this->SocClass->getUserIdByPaymentParams($this->params);
        $this->accessToken = User::getAccessToken($this->userId);
        if (isset($this->accessToken['error'])) {
            Response::sendResponse($this->SocClass->setPaymentError(1, $this->accessToken['error']), 200, $this->accessToken['error']);
        }
        $User = new User($this->userId, $this->accessToken, $this->config);
        $this->userInfo = $User->checkUserAuth();
        if (isset($this->userInfo['error'])) {
            Response::sendResponse($this->SocClass->setPaymentError(1, $this->userInfo['error']), 200, $this->userInfo['error']);
        }
    }

    /*
     * Начисление платежа
     */
    protected function setPayment()
    {
        if ($this->params['item'] === 'wheel_paid') {
            //Сделать начисление продукта
            Response::sendResponse(['response' => ['order_id' => $this->params['order_id'], 'app_order_id' => $this->params['order_id']]]);
        }
    }

}