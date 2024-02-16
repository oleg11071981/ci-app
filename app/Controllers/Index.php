<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Index extends ResourceController
{
    public function index()
    {
        return $this->respond(['test' => 'ok']);
    }

}