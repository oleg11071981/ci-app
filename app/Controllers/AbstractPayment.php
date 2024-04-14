<?php

namespace App\Controllers;

use App\Libraries\Response;
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

}