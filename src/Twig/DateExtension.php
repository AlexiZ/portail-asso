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

    public function formatDate(\DateTimeInterface $start, ?\DateTimeInterface $end = null): string
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

        // Cas sans date de fin
        if (!$end) {
            return $this->translator->trans('event.start_only', [
                '%date%' => $formatterDay->format($start),
                '%start%' => $formatterTime->format($start),
            ]);
        }

        // Cas même jour
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $this->translator->trans('event.single_day', [
                '%date%' => $formatterDay->format($start),
                '%start%' => $formatterTime->format($start),
                '%end%' => $formatterTime->format($end),
            ]);
        }

        // Cas plusieurs jours
        return $this->translator->trans('event.multiple_days', [
            '%start_date%' => $formatterDay->format($start),
            '%start_hour%' => $formatterTime->format($start),
            '%end_date%' => $formatterDay->format($end),
            '%end_hour%' => $formatterTime->format($end),
        ]);
    }
}
