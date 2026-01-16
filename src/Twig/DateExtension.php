<?php

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DateExtension extends AbstractExtension
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('format_date', [$this, 'formatDate']),
        ];
    }

    public function formatDate(\DateTimeInterface $start): string
    {
        $locale = 'fr_FR';

        // "19 septembre 2025"
        $formatterDay = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE
        );

        // "19:19"
        $formatterTime = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::SHORT
        );

        return $this->translator->trans('event.date', [
            '%date%' => $formatterDay->format($start),
            '%start%' => $formatterTime->format($start),
        ]);
    }
}
