<?php

namespace App\Libraries;

/**
 * Получение настроек игрового мира
 */
class WorldData
{
    /*
     * Настройки игрового мира
     */
    public array $worldData;

    public function __construct()
    {
        $this->worldData = [
            'tutorialSettings' => config('General/TutorialSettings')->tutorial_settings
        ];
    }

    public function getWorldData(): array
    {
        return $this->worldData;
    }
}