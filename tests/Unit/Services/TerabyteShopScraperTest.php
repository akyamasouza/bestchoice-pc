<?php

namespace Tests\Unit\Services;

use App\Services\CpuOffers\TerabyteShopScraper;
use PHPUnit\Framework\TestCase;

class TerabyteShopScraperTest extends TestCase
{
    public function test_it_parses_last_known_terabyte_offer_html(): void
    {
        $html = <<<'HTML'
        <html><body>
        <div>Novo 36 meses de garantia Processador AMD Ryzen 9 9950X3D, 4.3GHz (5.7GHz Turbo), 16-Cores 32-Threads, AM5, Sem Cooler, 100-100000719WOF CÓD: 100-100000719WOF Vendido por: TerabyteShop Todos vendidos Produto Indisponível O último preço praticado nesse produto foi de *R$ 5.319,99 à vista com 15% de desconto no boleto ou pix</div>
        </body></html>
        HTML;

        $offer = (new TerabyteShopScraper())->parseHtml($html, 'https://terabyte.test/cpu');

        $this->assertSame('terabyteshop', $offer->store);
        $this->assertSame('TerabyteShop', $offer->seller);
        $this->assertFalse($offer->isAvailable);
        $this->assertSame('out_of_stock', $offer->status);
        $this->assertSame(5319.99, $offer->pricePix);
        $this->assertSame(15, $offer->discountPercent);
    }
}
