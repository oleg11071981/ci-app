<?php

namespace App\Libraries;

class Response
{

    /*
      * Вывод ошибки
    */
    public static function sendResponse($responseBody, $status = 200, $error = null)
    {
        $Response = service('response');
        $Response->setStatusCode($status);
        $Response->setContentType('application/json');
        $Response->setJSON($responseBody);
        if (!empty($error)) {
            log_message('error', $error);
        }
        $Response->send();
        exit();
    }

}