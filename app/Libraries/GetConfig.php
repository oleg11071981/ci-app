<?php

namespace App\Libraries;

/**
 * Получение конфигов приложения
 */
class GetConfig
{
    final public static function get_config_data($path, $ad_id = null)
    {
        if (!empty($ad_id)) {
            $Settings = config('Personal/Settings');
            $ADIDs = $Settings->ADIDs;
            if (in_array($ad_id, $ADIDs)) {
                $config = config("AdId/$ad_id/$path");
                if ($config) {
                    return $config;
                }
            }
        }
        return config($path);
    }
}