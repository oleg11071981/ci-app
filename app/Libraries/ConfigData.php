<?php

namespace App\Libraries;

/**
 * Дополнительные настройки приложения
 */
class ConfigData
{
    /*
     * Дополнительные настройки приложения
     */
    public array $configData;

    public function __construct()
    {
        $this->configData = [
            'isSentryOn' => config('General/Settings')->isSentryOn
        ];
    }

    public function getConfigData(): array
    {
        return $this->configData;
    }
}