<?php

namespace App\Services\CpuOffers;

use App\DataTransferObjects\ScrapedCpuOfferData;
use App\Services\CpuOffers\Contracts\CpuStoreScraper;
use RuntimeException;

class TerabyteShopScraper extends AbstractCpuStoreScraper implements CpuStoreScraper
{
    public function store(): string
    {
        return 'terabyteshop';
    }

    public function parseHtml(string $html, string $url): ScrapedCpuOfferData
    {
        return $this->parseDocument($html, $this->visibleText($html), $url);
    }

    protected function parseDocument(string $html, string $text, string $url): ScrapedCpuOfferData
    {
        $sellerMatch = $this->matchOrFail(
            $text,
            '/Vendido por:\\s*(.+?)\\s*(?:Todos vendidos|Disponível|★★★★★|De:|Produto Indisponível)/u',
            'Unable to parse TerabyteShop seller.',
        );

        $isUnavailable = str_contains(mb_strtolower($text), 'produto indisponível') || str_contains(mb_strtolower($text), 'todos vendidos');
        $isAvailable = ! $isUnavailable && str_contains(mb_strtolower($text), 'disponível');

        if ($isUnavailable && preg_match('/O último preço praticado nesse produto foi de\\s*\\*?R\\$\\s*([\\d\\.,]+)\\s*à vista(?:\\s*com\\s*(\\d+)% de desconto no boleto ou pix)?/iu', $text, $priceMatch)) {
            return new ScrapedCpuOfferData(
                store: $this->store(),
                url: $url,
                productName: $this->extractTerabyteTitle($text),
                seller: $this->normalizeSeller($sellerMatch[1]),
                status: 'out_of_stock',
                isAvailable: false,
                listPrice: null,
                pricePix: $this->parseMoney($priceMatch[1]),
                priceCard: null,
                installments: null,
                installmentPrice: null,
                discountPercent: isset($priceMatch[2]) ? (int) $priceMatch[2] : null,
                meta: [
                    'price_type' => 'last_known_price',
                ],
            );
        }

        $priceMatch = $this->matchOrFail(
            $text,
            '/De:\\s*R\\$\\s*([\\d\\.,]+)\\s*por:\\s*R\\$\\s*([\\d\\.,]+)\\s*à vista(?:\\s*com\\s*(\\d+)% de desconto no boleto ou pix)?\\s*R\\$\\s*([\\d\\.,]+)\\s*(\\d+)x de R\\$\\s*([\\d\\.,]+)\\s*sem juros no cartão/iu',
            'Unable to parse TerabyteShop pricing block.',
        );

        return new ScrapedCpuOfferData(
            store: $this->store(),
            url: $url,
            productName: $this->extractTerabyteTitle($text),
            seller: $this->normalizeSeller($sellerMatch[1]),
            status: $isAvailable ? 'in_stock' : 'unknown',
            isAvailable: $isAvailable,
            listPrice: $this->parseMoney($priceMatch[1]),
            pricePix: $this->parseMoney($priceMatch[2]),
            priceCard: $this->parseMoney($priceMatch[4]),
            installments: (int) $priceMatch[5],
            installmentPrice: $this->parseMoney($priceMatch[6]),
            discountPercent: isset($priceMatch[3]) ? (int) $priceMatch[3] : null,
            meta: [],
        );
    }

    private function extractTerabyteTitle(string $text): ?string
    {
        if (preg_match('/(?:Novo\\s+\\d+\\s+meses de garantia\\s+)?(Processador AMD .*?100-[A-Z0-9-]+)\\s+CÓD:/iu', $text, $matches)) {
            return trim($matches[1]);
        }

        return $this->extractTitleFromText($text);
    }
}
