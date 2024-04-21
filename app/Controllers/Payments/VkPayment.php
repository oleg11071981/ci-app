<?php

namespace App\Controllers\Payments;

use App\Controllers\AbstractPayment;

/**
 * Платёжка в социальной сети Vk
 */
class VkPayment extends AbstractPayment
{
    public function index() {

        $this->checkSignature();

        $this->checkProduct();

        $this->checkPayment();

        $this->checkUserInfo();

        $this->setPayment();

    }
}