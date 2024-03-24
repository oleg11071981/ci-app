<?php

namespace App\Libraries;

/**
 * Дополнительные фильтры для приложения
 */
class App
{

    /*
     * Блокировка приложения по параметру
     */
    public static function blockAppByParam($appStop): void
    {
        if ($appStop === 1) {
            self::sendResponseError('Приложение заблокировано', 'auth_error');
        }
    }

    /*
     * Блокировка приложения по IP
     */
    public static function blockAppByIp($ip, $blockedIps): void
    {
        if (in_array($ip, $blockedIps)) {
            self::sendResponseError("Для Вашего IP приложение недоступно: $ip", 'auth_error');
        }
    }

    /*
     * Вывод ошибки
     */
    public static function sendResponseError($error, $error_key, $status = 403)
    {
        $Response = service('response');
        $Response->setStatusCode($status);
        $Response->setContentType('application/json');
        $Response->setJSON([
            'error' => $error,
            'error_key' => $error_key
        ]);
        log_message('error', $error);
        $Response->send();
        exit();
    }

}