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
        $this->params = $this->checkSignature();

        $this->UserInfo = $this->getUserInfo();

        $UserInfoFromDb = $this->getUserInfoFromDb();

        return $this->respond($UserInfoFromDb, 200);
    }

}