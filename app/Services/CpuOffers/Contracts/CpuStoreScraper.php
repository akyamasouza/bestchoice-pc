<?php

namespace App\Services\CpuOffers\Contracts;

use App\DataTransferObjects\ScrapedCpuOfferData;

interface CpuStoreScraper
{
    public function store(): string;

    public function scrape(string $url): ScrapedCpuOfferData;
}
