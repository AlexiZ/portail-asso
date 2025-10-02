<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('mark', [$this, 'mark'], ['is_safe' => ['html']]),
        ];
    }

    public function mark(string $text, ?string $query): string
    {
        if (!$query) {
            return $text;
        }

        $pattern = '/'.preg_quote($query, '/').'/i';

        return preg_replace($pattern, '<mark>$0</mark>', $text);
    }
}
