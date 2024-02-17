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
         * Проверяем был ли уже зарегистрирован пользователь
         * Если не был, то регистрируем пользователя
         */
        $UserInfoFromDb = $this->getUserInfoFromDb();

        /*
         * Возвращаем ответ для клиента
         */
        return $this->respond($UserInfoFromDb, 200);

    }

}