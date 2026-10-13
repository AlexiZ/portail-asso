<?php

namespace App\Tests\Twig;

use App\Twig\ObfuscateExtension;
use PHPUnit\Framework\TestCase;

class ObfuscateExtensionTest extends TestCase
{
    public function testValueIsNotRenderedInClear(): void
    {
        $html = (new ObfuscateExtension())->obfuscatedLink('contact@example.org');

        $this->assertStringNotContainsString('contact@example.org', $html);
        $this->assertStringNotContainsString('@', $html);
        $this->assertStringContainsString('data-obfuscated="'.base64_encode('gro.elpmaxe@tcatnoc').'"', $html);
    }
}
