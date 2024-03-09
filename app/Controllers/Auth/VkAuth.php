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
        $this->checkSignature();

        $this->getUserInfo();

        $this->getUserInfoFromDb();

        $data = $this->setClientData();

        return $this->respond($data, 200);
    }

}