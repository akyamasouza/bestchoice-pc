<?php

namespace Tests\Unit\Services;

use App\Services\CpuOffers\KabumScraper;
use PHPUnit\Framework\TestCase;

class KabumScraperTest extends TestCase
{
    public function test_it_parses_kabum_offer_html(): void
    {
        $html = <<<'HTML'
        <html><body>
        <h1>Processador AMD Ryzen 9 9950x3d, 4,4 GHz, (Máx Boos Clock Até 5,5 GHz), Cache 144MB, 16 Núcleos, Threads 32, AM5 - 100-100000719WOF</h1>
        <div>Vendido e entregue por: <b>KaBuM!</b></div>
        <span class="line-through">R$ 7.000,00</span>
        <h4>R$ 4.299,99</h4>
        <span>À vista no PIX</span>
        <span>Em até 10x de R$ 429,99 sem juros</span>
        <div>Em estoque</div>
        <script type="application/ld+json">
        {"offers":{"availability":"https://schema.org/InStock"}}
        </script>
        </body></html>
        HTML;

        $offer = (new KabumScraper())->parseHtml($html, 'https://kabum.test/cpu');

        $this->assertSame('kabum', $offer->store);
        $this->assertSame('KaBuM!', $offer->seller);
        $this->assertTrue($offer->isAvailable);
        $this->assertSame(7000.00, $offer->listPrice);
        $this->assertSame(4299.99, $offer->pricePix);
        $this->assertSame(10, $offer->installments);
        $this->assertSame(429.99, $offer->installmentPrice);
    }
}
