<?php

namespace App\Controllers\Settings;

use App\Controllers\AbstractModule;
use App\Libraries\App;

/*
 * Смена версии языка
 */

class Lang extends AbstractModule
{
    public function index()
    {
        $lang = (int)$this->request->getPost('lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!in_array($lang, $this->config->langVersions)) {
            App::sendResponseError('Неверный входящий параметр, lang: ' . $lang, $this->errorKey);
        } else {
            $this->userInfo['lang'] = $lang;
            $this->userInfo = $this->User->updateUser($this->userInfo, ['lang' => $lang]);
            if (isset($this->userInfo['error'])) {
                App::sendResponseError($this->userInfo['error'], $this->errorKey);
            } else {
                $this->clientData['lang'] = $this->userInfo['lang'];
            }
        }
        return $this->respond($this->clientData, 200);
    }
}