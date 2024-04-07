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
        $this->checkLangParam($lang);
        $this->userInfo['lang'] = $lang;
        $this->changeUserData(['lang' => $lang]);
        $this->clientData['lang'] = $this->userInfo['lang'];
        return $this->respond($this->clientData, 200);
    }

    private function checkLangParam($lang): void
    {
        if (!in_array($lang, $this->config->langVersions)) {
            App::sendResponseError('Неверный входящий параметр, lang: ' . $lang, $this->errorKey);
        }
    }

}