<?php

namespace App\Twig;

use App\Service\SettingsService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SettingsTwigExtension extends AbstractExtension implements GlobalsInterface
{
    private SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function getGlobals(): array
    {
        return [
            'settings' => $this->settings,
        ];
    }
}
