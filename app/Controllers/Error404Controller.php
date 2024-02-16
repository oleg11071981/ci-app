<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

/**
 * Кастомизация вывода 404 ошибки
 */
class Error404Controller extends ResourceController
{
    public function index()
    {
        //Выводим 404 ошибку
        return $this->respond(['error' => 'page or method not found', 'error_key' => 'error404'], 404);
    }
}