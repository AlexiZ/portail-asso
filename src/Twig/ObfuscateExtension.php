<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Rend un lien mailto:/tel: dont la valeur n'apparaît pas en clair dans le HTML.
 * Le lien est reconstruit côté navigateur par assets/app.js.
 */
class ObfuscateExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('obfuscated_link', [$this, 'obfuscatedLink'], ['is_safe' => ['html']]),
        ];
    }

    public function obfuscatedLink(string $value, string $scheme = 'mailto', ?string $label = null, string $class = ''): string
    {
        return \sprintf(
            '<a href="#" class="%s" data-obfuscated="%s" data-scheme="%s"%s>%s</a>',
            htmlspecialchars($class, ENT_QUOTES),
            base64_encode(strrev($value)),
            htmlspecialchars($scheme, ENT_QUOTES),
            null !== $label ? ' data-keep-label' : '',
            null !== $label ? htmlspecialchars($label, ENT_QUOTES) : '…',
        );
    }
}
