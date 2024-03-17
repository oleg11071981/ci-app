<?php

namespace App\Libraries;

class ConfigData
{
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