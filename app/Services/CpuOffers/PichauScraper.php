<?php

namespace App\Services\CpuOffers;

use App\DataTransferObjects\ScrapedCpuOfferData;
use App\Services\CpuOffers\Contracts\CpuStoreScraper;
use RuntimeException;

class PichauScraper extends AbstractCpuStoreScraper implements CpuStoreScraper
{
    public function store(): string
    {
        return 'pichau';
    }

    public function parseHtml(string $html, string $url): ScrapedCpuOfferData
    {
        return $this->parseDocument($html, $this->visibleText($html), $url);
    }

    protected function parseDocument(string $html, string $text, string $url): ScrapedCpuOfferData
    {
        $priceMatch = $this->matchOrFail(
            $text,
            '/de R\\$\\s*([\\d\\.,]+)\\s*por:\\s*à vista\\s*R\\$\\s*([\\d\\.,]+)\\s*no PIX com\\s*(\\d+)% desconto\\s*R\\$\\s*([\\d\\.,]+)\\s*em até\\s*(\\d+)\\s*x de R\\$\\s*([\\d\\.,]+)\\s*sem juros no cartão/iu',
            'Unable to parse Pichau pricing block.',
        );

        $availability = preg_match('/product:availability.*?(outofstock|instock)/i', $html, $availabilityMatch)
            ? strtolower($availabilityMatch[1])
            : 'unknown';

        return new ScrapedCpuOfferData(
            store: $this->store(),
            url: $url,
            productName: $this->extractPichauTitle($html, $text),
            seller: 'Pichau',
            status: $availability === 'instock' ? 'in_stock' : ($availability === 'outofstock' ? 'out_of_stock' : 'unknown'),
            isAvailable: $availability === 'instock',
            listPrice: $this->parseMoney($priceMatch[1]),
            pricePix: $this->parseMoney($priceMatch[2]),
            priceCard: $this->parseMoney($priceMatch[4]),
            installments: (int) $priceMatch[5],
            installmentPrice: $this->parseMoney($priceMatch[6]),
            discountPercent: (int) $priceMatch[3],
            meta: [
                'raw_availability' => $availability,
            ],
        );
    }

    private function extractPichauTitle(string $html, string $text): ?string
    {
        $title = $this->extractTagText($html, '/<h1[^>]*>(.*?)<\\/h1>/isu');

        if ($title !== null) {
            return $title;
        }

        if (preg_match('/(Processador AMD .*?100-[A-Z0-9-]+(?:-BR)?)\\s*Sku:/iu', $text, $matches)) {
            return trim($matches[1]);
        }

        return $this->extractTitleFromText($text);
    }
}
