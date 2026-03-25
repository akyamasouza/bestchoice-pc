<?php

namespace Tests\Unit\Services;

use App\Services\CpuOffers\PichauScraper;
use PHPUnit\Framework\TestCase;

class PichauScraperTest extends TestCase
{
    public function test_it_parses_pichau_offer_html(): void
    {
        $html = <<<'HTML'
        <html><body>
        <h1>Processador AMD Ryzen 9 9950X3D, 16-Core, 32-Threads, 4.3GHz (5.7GHz Turbo), Cache 144MB, AM5, 100-100000719WOF-BR</h1>
        <div>Sku: 100-100000719WOF-BR</div>
        <div>Marca: AMD</div>
        <div>de R$ 6,999.99 por: à vista R$ 3,959.99 no PIX com 15% desconto R$ 4,658.81 em até 12x de R$ 388.23 sem juros no cartão</div>
        <meta name="product:availability" content="outofstock">
        </body></html>
        HTML;

        $offer = (new PichauScraper())->parseHtml($html, 'https://pichau.test/cpu');

        $this->assertSame('pichau', $offer->store);
        $this->assertSame('Pichau', $offer->seller);
        $this->assertFalse($offer->isAvailable);
        $this->assertSame(6999.99, $offer->listPrice);
        $this->assertSame(3959.99, $offer->pricePix);
        $this->assertSame(4658.81, $offer->priceCard);
        $this->assertSame(12, $offer->installments);
        $this->assertSame(388.23, $offer->installmentPrice);
    }
}
