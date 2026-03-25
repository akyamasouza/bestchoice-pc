<?php

namespace Tests\Unit\Services;

use App\Services\CpuOffers\AmazonScraper;
use PHPUnit\Framework\TestCase;

class AmazonScraperTest extends TestCase
{
    public function test_it_parses_amazon_offer_html(): void
    {
        $html = <<<'HTML'
        <html>
        <head>
            <meta name="title" content="Processador AMD Ryzen 9 9950X3D (AM5/ 16 Cores/ 32 Threads/ 5.7 GHz/ 144Mb Cache/Radeon Graphics/Sem Cooler) | Amazon.com.br" />
        </head>
        <body>
            <div class="a-section apex-core-price-identifier">
                <span class="a-price aok-align-center apex-pricetopay-value">
                    <span class="a-offscreen">R$3.959,99</span>
                </span>
            </div>
            <div id="availability">
                <span class="a-size-medium a-color-success primary-availability-message"> Em estoque </span>
            </div>
            <div id="desktop-merchant-info">
                <span class="offer-display-feature-text-message">Amazon.com.br</span>
            </div>
        </body>
        </html>
        HTML;

        $offer = (new AmazonScraper())->parseHtml($html, 'https://amazon.test/cpu');

        $this->assertSame('amazon', $offer->store);
        $this->assertSame('Amazon.com.br', $offer->seller);
        $this->assertTrue($offer->isAvailable);
        $this->assertSame('in_stock', $offer->status);
        $this->assertSame(3959.99, $offer->pricePix);
        $this->assertSame(3959.99, $offer->priceCard);
    }
}
