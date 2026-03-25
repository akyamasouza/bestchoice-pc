<?php

namespace App\Services\CpuOffers;

use App\DataTransferObjects\ScrapedCpuOfferData;
use App\Services\CpuOffers\Contracts\CpuStoreScraper;
use RuntimeException;

class KabumScraper extends AbstractCpuStoreScraper implements CpuStoreScraper
{
    public function store(): string
    {
        return 'kabum';
    }

    public function parseHtml(string $html, string $url): ScrapedCpuOfferData
    {
        return $this->parseDocument($html, $this->visibleText($html), $url);
    }

    protected function parseDocument(string $html, string $text, string $url): ScrapedCpuOfferData
    {
        $sellerMatch = $this->matchOrFail(
            $html,
            [
                '/Vendido e entregue por:\\s*(?:<\\/span>\\s*)?<b[^>]*>(.*?)<\\/b>/isu',
            ],
            'Unable to parse KaBuM seller.',
        );

        $priceMatch = $this->matchOrFail(
            $text,
            '/R\\$\\s*([\\d\\.,]+)\\s*R\\$\\s*([\\d\\.,]+).*?PIX\\s*Em.*?(\\d+)\\s*x de R\\$\\s*([\\d\\.,]+)\\s*sem juros/iu',
            'Unable to parse KaBuM pricing block.',
        );

        $availability = preg_match('/"availability"\\s*:\\s*"https:\\/\\/schema\\.org\\/([^"]+)"/i', $html, $availabilityMatch)
            ? strtolower($availabilityMatch[1])
            : (str_contains(mb_strtolower($text), 'em estoque') ? 'instock' : 'unknown');

        return new ScrapedCpuOfferData(
            store: $this->store(),
            url: $url,
            productName: $this->extractKabumTitle($html, $text),
            seller: $this->normalizeSeller($sellerMatch[1], [
                'Sobre o produto',
                'Resumo gerado por IA',
                'Processador AMD',
                'R$',
            ]),
            status: $availability === 'instock' ? 'in_stock' : ($availability === 'outofstock' ? 'out_of_stock' : 'unknown'),
            isAvailable: $availability === 'instock',
            listPrice: $this->parseMoney($priceMatch[1]),
            pricePix: $this->parseMoney($priceMatch[2]),
            priceCard: round((int) $priceMatch[3] * $this->parseMoney($priceMatch[4]), 2),
            installments: (int) $priceMatch[3],
            installmentPrice: $this->parseMoney($priceMatch[4]),
            discountPercent: null,
            meta: [
                'raw_availability' => $availability,
            ],
        );
    }

    private function extractKabumTitle(string $html, string $text): ?string
    {
        $title = $this->extractTagText($html, '/<h1[^>]*>(.*?)<\\/h1>/isu');

        if ($title !== null) {
            return $title;
        }

        if (preg_match('/Código\\s+\\d+.*?(Processador AMD .*?100-[A-Z0-9-]+)\\s+Vendido e entregue por:/iu', $text, $matches)) {
            return trim($matches[1]);
        }

        return $this->extractTitleFromText($text);
    }
}
