<?php

namespace App\Services\CpuOffers;

use App\Models\Cpu;
use App\Models\CpuOffer;
use App\Services\CpuOffers\Contracts\CpuStoreScraper;
use RuntimeException;
use Throwable;

class CpuOfferSyncService
{
    public function __construct(
        private readonly AmazonScraper $amazonScraper,
        private readonly KabumScraper $kabumScraper,
        private readonly PichauScraper $pichauScraper,
        private readonly TerabyteShopScraper $terabyteShopScraper,
    ) {
    }

    /**
     * @param  list<string>  $onlyStores
     * @return array{offers: list<CpuOffer>, errors: list<string>}
     */
    public function syncCpu(Cpu $cpu, array $onlyStores = []): array
    {
        $urls = collect($cpu->store_urls ?? [])
            ->filter(fn (mixed $value, string $store): bool => filled($value) && ($onlyStores === [] || in_array($store, $onlyStores, true)));

        $offers = [];
        $errors = [];

        foreach ($urls as $store => $url) {
            try {
                $scraper = $this->scraperFor($store);
                $data = $scraper->scrape($url);
                $offer = CpuOffer::query()
                    ->where('cpu_id', (string) $cpu->getKey())
                    ->where('store', $store)
                    ->first() ?? new CpuOffer([
                        'cpu_id' => (string) $cpu->getKey(),
                        'store' => $store,
                    ]);

                $timestamp = now();

                $offer->fill([
                    'cpu_name' => $cpu->name,
                    'cpu_sku' => $cpu->sku,
                    'url' => $url,
                    'product_name' => $this->resolveProductName($cpu->name, $data->productName),
                    'seller' => $data->seller,
                    'status' => $data->status,
                    'is_available' => $data->isAvailable,
                    'currency' => 'BRL',
                    'list_price' => $data->listPrice,
                    'price_pix' => $data->pricePix,
                    'price_card' => $data->priceCard,
                    'installments' => $data->installments,
                    'installment_price' => $data->installmentPrice,
                    'discount_percent' => $data->discountPercent,
                    'checked_at' => $timestamp,
                    'updated_at' => $timestamp,
                    'meta' => $data->meta,
                ]);

                if (! $offer->exists || blank($offer->created_at)) {
                    $offer->created_at = $timestamp;
                }

                $offer->save();

                if (blank($offer->created_at)) {
                    CpuOffer::query()
                        ->where('_id', $offer->getKey())
                        ->update(['created_at' => $timestamp]);

                    $offer->created_at = $timestamp;
                }

                $offers[] = $offer;
            } catch (Throwable $exception) {
                $errors[] = "{$store}: {$exception->getMessage()}";
            }
        }

        return [
            'offers' => $offers,
            'errors' => $errors,
        ];
    }

    private function scraperFor(string $store): CpuStoreScraper
    {
        return match ($store) {
            'amazon' => $this->amazonScraper,
            'kabum' => $this->kabumScraper,
            'pichau' => $this->pichauScraper,
            'terabyteshop' => $this->terabyteShopScraper,
            default => throw new RuntimeException("Unsupported store [{$store}]."),
        };
    }

    private function resolveProductName(string $cpuName, ?string $scrapedProductName): string
    {
        if (blank($scrapedProductName) || mb_strlen($scrapedProductName) > 200) {
            return $cpuName;
        }

        return $scrapedProductName;
    }
}
