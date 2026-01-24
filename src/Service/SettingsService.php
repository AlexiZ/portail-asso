<?php

namespace App\Service;

use App\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

class SettingsService
{
    private EntityManagerInterface $em;
    private CacheItemPoolInterface $cache;
    private string $cachePrefix = 'setting_';

    public function __construct(EntityManagerInterface $em, CacheItemPoolInterface $cache)
    {
        $this->em = $em;
        $this->cache = $cache;
    }

    /**
     * Retourne la valeur du paramètre en priorité depuis le cache.
     * Si absent, récupère depuis la base et met en cache.
     */
    public function get(string $key): mixed
    {
        $cacheKey = $this->cachePrefix.$key;
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        $setting = $this->em->getRepository(Setting::class)->findOneBy(['key' => $key]);
        if (!$setting) {
            return null;
        }

        $item->set($setting->getValue());
        $this->cache->save($item);

        return $setting->getValue();
    }

    /**
     * Optionnel : pour forcer la mise à jour du cache après modification.
     */
    public function refresh(string $key, mixed $value = null): void
    {
        $cacheKey = $this->cachePrefix.$key;
        $item = $this->cache->getItem($cacheKey);

        if (!$value) {
            $setting = $this->em->getRepository(Setting::class)->findOneBy(['key' => $key]);
            $value = $setting?->getValue();
        }

        $item->set($value);
        $this->cache->save($item);
    }
}
