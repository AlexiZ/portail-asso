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
        $text = htmlspecialchars($text, ENT_QUOTES);
        if (!$query) {
            return $text;
        }

        $pattern = '/'.preg_quote(htmlspecialchars($query, ENT_QUOTES), '/').'/i';

        return preg_replace($pattern, '<mark>$0</mark>', $text);
    }
}
