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

        return $this->respond($this->params,200);

    }
}