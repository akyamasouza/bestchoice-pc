<?php

namespace Tests\Unit\Services\Telegram;

use App\Services\Telegram\TelegramChannelSearchService;
use ReflectionClass;
use Tests\TestCase;

class TelegramChannelSearchServiceTest extends TestCase
{
    public function test_it_normalizes_channel_messages(): void
    {
        $service = new TelegramChannelSearchService;
        $method = (new ReflectionClass($service))->getMethod('normalizeMessage');

        $payload = [
            'id' => 321,
            'date' => 1_711_370_800,
            'message' => "Ryzen 7 9800X3D\nR$ 2.999,90\nKabum",
            'views' => 1234,
            'forwards' => 12,
        ];

        $normalized = $method->invoke($service, $payload, '@pcbuildwizard');

        $this->assertSame(321, $normalized['id']);
        $this->assertSame('Ryzen 7 9800X3D'."\n".'R$ 2.999,90'."\n".'Kabum', $normalized['text']);
        $this->assertSame(1234, $normalized['views']);
        $this->assertSame(12, $normalized['forwards']);
        $this->assertSame('https://t.me/pcbuildwizard/321', $normalized['url']);
        $this->assertNotNull($normalized['date_iso']);
    }
}
