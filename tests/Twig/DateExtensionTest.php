<?php

namespace App\Tests\Twig;

use App\Entity\Event;
use App\Twig\DateExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class DateExtensionTest extends TestCase
{
    private function format(string $startAt, ?string $endAt = null): string
    {
        $translator = new Translator('fr', new MessageFormatter());
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', __DIR__.'/../../translations/messages+intl-icu.fr.yaml', 'fr', 'messages+intl-icu');

        $event = (new Event())->setStartAt(new \DateTime($startAt))->setEndAt($endAt ? new \DateTime($endAt) : null);

        return (new DateExtension($translator))->formatEventDates($event);
    }

    public function testFormats(): void
    {
        $this->assertSame('Le mardi 3 novembre 2026 à 20h', $this->format('2026-11-03 20:00'));
        $this->assertSame('Le mardi 3 novembre 2026 de 20h30 à 23h', $this->format('2026-11-03 20:30', '2026-11-03 23:00'));
        $this->assertSame('Du mardi 3 novembre 2026 à 20h au mercredi 4 novembre 2026 à 10h', $this->format('2026-11-03 20:00', '2026-11-04 10:00'));
    }
}
