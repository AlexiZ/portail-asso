<?php

namespace App\Twig;

use App\Entity\Event;
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
            new TwigFunction('format_event_dates', [$this, 'formatEventDates']),
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
        $formatterDay->setPattern('EEEE d MMMM yyyy');

        // "19:19"
        $formatterTime = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::SHORT
        );

        return $this->translator->trans('event.date', [
            '%date%' => ucfirst($formatterDay->format($start)),
            '%start%' => $formatterTime->format($start),
        ]);
    }

    /**
     * "Le mardi 3 novembre 2026 à 20h", "Le mardi 3 novembre 2026 de 20h à 23h"
     * or "Du mardi 3 novembre 2026 à 20h au mercredi 4 novembre 2026 à 10h".
     */
    public function formatEventDates(Event $event): string
    {
        $start = $event->getStartAt();
        $end = $event->getEndAt();
        $key = match (true) {
            null === $end => 'event.dates.single',
            $start->format('Y-m-d') === $end->format('Y-m-d') => 'event.dates.same_day',
            default => 'event.dates.range',
        };

        return $this->translator->trans($key, [
            'startDay' => $this->formatDay($start),
            'startTime' => $this->formatTime($start),
            'endDay' => $end ? $this->formatDay($end) : '',
            'endTime' => $end ? $this->formatTime($end) : '',
        ]);
    }

    private function formatDay(\DateTimeInterface $date): string
    {
        // "mardi 3 novembre 2026"
        return (new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, pattern: 'EEEE d MMMM yyyy'))->format($date);
    }

    private function formatTime(\DateTimeInterface $date): string
    {
        // "20h", "20h30"
        return $date->format('G').'h'.('00' === $date->format('i') ? '' : $date->format('i'));
    }
}
