<?php

namespace App\Dto;

use App\Entity\Setting;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class SettingsCollection
{
    private Collection $settings;

    public function __construct(array $settings = [])
    {
        $this->settings = new ArrayCollection();
        foreach ($settings as $setting) {
            $this->addSetting($setting);
        }
    }

    public function getSettings(): Collection
    {
        return $this->settings;
    }

    public function addSetting(Setting $setting): void
    {
        if (!$this->settings->contains($setting)) {
            $this->settings->add($setting);
        }
    }

    public function __get($name)
    {
        if ('settings' === $name) {
            return $this->settings;
        }

        throw new \InvalidArgumentException(sprintf('Property "%s" does not exist.', $name));
    }

    public function __set($name, $value)
    {
        if ('settings' === $name) {
            $this->settings = $value;
        } else {
            throw new \InvalidArgumentException(sprintf('Property "%s" does not exist.', $name));
        }
    }
}
