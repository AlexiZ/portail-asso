<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LinkExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('web_link', [$this, 'webLink']),
            new TwigFunction('facebook_link', [$this, 'facebookLink']),
            new TwigFunction('instagram_link', [$this, 'instagramLink']),
        ];
    }

    public function webLink(string $username): array
    {
        return $this->getLink('https://', $username);
    }

    public function facebookLink(string $username): array
    {
        return $this->getLink('https://www.facebook.com/', $username);
    }

    public function instagramLink(string $username): array
    {
        return $this->getLink('https://www.instagram.com/', $username);
    }

    public function getLink(string $baseUrl, string $username): array
    {
        // S'assurer que l'URL commence par le bon préfixe
        if (!str_starts_with($username, $baseUrl)) {
            $fullUrl = $baseUrl.ltrim($username, '/');
        } else {
            $fullUrl = $username;
        }

        // Retourner à la fois le lien complet et la partie affichée
        return [
            'href' => $fullUrl,
            'label' => str_replace($baseUrl, '', $fullUrl),
        ];
    }
}
