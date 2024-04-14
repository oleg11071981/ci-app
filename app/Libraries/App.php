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
     * Проверка обязательных параметров запроса
     */
    public static function checkParams($requiredParams, $params): void
    {
        foreach ($requiredParams as $param) {
            if (!isset($params[$param])) {
                self::sendResponseError("В запросе отсутствуют обязательный параметр: " . $param, 'auth_error');
            }
        }
    }

    /*
     * Проверка версии конфига
     */
    public static function checkConfigVersion($version, $configVersion): void
    {
        if ($version != $configVersion) {
            self::sendResponseError("Игра обновилась. Приложение будет перезагружено, configVersion: " . $configVersion, 'version_error');
        }
    }

    /*
     * Вывод ошибки
     */
    public static function sendResponseError($error, $error_key, $status = 403): void
    {
        Response::sendResponse(
            [
                'error' => $error,
                'error_key' => $error_key
            ],
            $status,
            $error
        );
    }

}