<?php

namespace App\DataTransferObjects;

final readonly class ScrapedCpuOfferData
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $store,
        public string $url,
        public ?string $productName,
        public ?string $seller,
        public string $status,
        public bool $isAvailable,
        public ?float $listPrice,
        public ?float $pricePix,
        public ?float $priceCard,
        public ?int $installments,
        public ?float $installmentPrice,
        public ?int $discountPercent,
        public array $meta = [],
    ) {
    }
}
