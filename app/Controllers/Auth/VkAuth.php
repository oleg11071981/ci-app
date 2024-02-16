<?php

namespace App\Controllers\Auth;

use App\Controllers\AbstractAuth;

/*
 * Авторизация в социальной сети ВК
 */

class VkAuth extends AbstractAuth
{

    public function index()
    {
        /*
         * Проверяем подпись запроса
         */
        $this->params = $this->checkSignature();

        /*
         * Информация о пользователе
         */
        $this->UserInfo = $this->getUserInfo();

        /*
         * Возвращаем ответ для клиента
         */
        return $this->respond($this->UserInfo, 200);

    }

}