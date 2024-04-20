<?php

namespace App\Controllers\Video;

use App\Controllers\AbstractModule;
use App\Libraries\VideoStat;

/*
 * Подсчёт Interstetial рекламы
 */

class Video_interstetial extends AbstractModule
{

    public function index()
    {
        $video_stat = $this->request->getPost('video_stat', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'undef';
        VideoStat::saveData($video_stat, $this->userInfo);
        return $this->respond($this->clientData, 200);
    }

}