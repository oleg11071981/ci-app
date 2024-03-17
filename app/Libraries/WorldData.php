<?php

namespace App\Libraries;

class WorldData
{
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