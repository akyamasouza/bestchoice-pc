<?php

namespace App\Services\CpuOffers;

use App\DataTransferObjects\ScrapedCpuOfferData;

class AmazonScraper extends AbstractCpuStoreScraper
{
    public function store(): string
    {
        return 'amazon';
    }

    public function parseHtml(string $html, string $url): ScrapedCpuOfferData
    {
        return $this->parseDocument($html, $this->visibleText($html), $url);
    }

    protected function parseDocument(string $html, string $text, string $url): ScrapedCpuOfferData
    {
        $priceMatch = $this->firstMatch(
            $html,
            [
                '/"displayPrice":"R\\$\\s*([\\d\\.,]+)/u',
                '/"priceAmount":\\s*([0-9]+\\.[0-9]{2})/u',
                '/<span class="a-offscreen">\\s*(R\\$\\s*[\\d\\.,]+)\\s*<\\/span>/iu',
            ],
        );

        $availabilityText = $this->extractTagText(
            $html,
            [
                '/<div id="availability"[^>]*>.*?<span[^>]*>(.*?)<\\/span>/isu',
            ],
        ) ?? (str_contains(mb_strtolower($text), 'em estoque') ? 'Em estoque' : null);

        $isAvailable = $availabilityText !== null
            && ! str_contains(mb_strtolower($availabilityText), 'indispon')
            && str_contains(mb_strtolower($availabilityText), 'estoque');

        $seller = $this->extractTagText(
            $html,
            [
                '/offer-display-feature-text-message">\\s*(.*?)\\s*<\\/span>/isu',
                '/Enviado\\s*\\/\\s*Vendido\\s*<\/span>.*?<span[^>]*>(.*?)<\\/span>/isu',
            ],
        ) ?? 'Amazon.com.br';

        $title = $this->extractMetaContent($html, 'title')
            ?? $this->extractTagText($html, '/<title>(.*?)<\\/title>/isu')
            ?? 'Amazon';

        $title = trim((string) preg_replace('/\\s*\\|\\s*Amazon\\.com\\.br$/iu', '', $title));

        $price = $priceMatch === null ? null : $this->parseMoney($priceMatch[1]);

        return new ScrapedCpuOfferData(
            store: $this->store(),
            url: $url,
            productName: $title,
            seller: $this->normalizeSeller($seller),
            status: $isAvailable ? 'in_stock' : 'out_of_stock',
            isAvailable: $isAvailable,
            listPrice: null,
            pricePix: $price,
            priceCard: $price,
            installments: null,
            installmentPrice: null,
            discountPercent: null,
            meta: [
                'raw_availability' => $availabilityText,
            ],
        );
    }
}
