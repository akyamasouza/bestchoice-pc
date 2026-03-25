<?php

use App\Models\Cpu;
use App\Services\CpuOffers\CpuOfferSyncService;
use App\Services\Telegram\TelegramChannelSearchService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cpu-offers:sync {--cpu=} {--store=*}', function (CpuOfferSyncService $syncService) {
    $cpuFilter = $this->option('cpu');
    $stores = array_values(array_filter((array) $this->option('store')));

    $cpus = Cpu::query()
        ->get()
        ->filter(fn (Cpu $cpu): bool => blank($cpuFilter)
            || (string) $cpu->getKey() === $cpuFilter
            || $cpu->sku === $cpuFilter
            || $cpu->name === $cpuFilter)
        ->values();

    if ($cpus->isEmpty()) {
        $this->error('No CPUs found for synchronization.');

        return Command::FAILURE;
    }

    foreach ($cpus as $cpu) {
        $this->newLine();
        $this->info("Syncing {$cpu->name}");

        $result = $syncService->syncCpu($cpu, $stores);

        foreach ($result['offers'] as $offer) {
            $price = $offer->price_pix !== null ? 'R$ '.number_format($offer->price_pix, 2, ',', '.') : 'n/a';
            $this->line(" - {$offer->store}: {$offer->status} | {$price}");
        }

        foreach ($result['errors'] as $error) {
            $this->warn(" - {$error}");
        }
    }

    return Command::SUCCESS;
})->purpose('Synchronize CPU offers from configured store URLs.');

Artisan::command('telegram:search-channel {query} {--channel=} {--limit=10} {--json}', function (TelegramChannelSearchService $searchService) {
    $query = (string) $this->argument('query');
    $channel = $this->option('channel');
    $limit = max(1, (int) $this->option('limit'));
    $asJson = (bool) $this->option('json');

    $results = $searchService->searchChannel($query, $channel ?: null, $limit);

    if ($results->isEmpty()) {
        $this->warn('No messages found for this query.');

        return Command::SUCCESS;
    }

    if ($asJson) {
        $this->line(json_encode($results->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }

    foreach ($results as $result) {
        $this->newLine();
        $this->info("Message #{$result['id']}");
        $this->line('Date: '.($result['date_iso'] ?? 'n/a'));
        $this->line('Views: '.($result['views'] ?? 'n/a'));
        $this->line('Forwards: '.($result['forwards'] ?? 'n/a'));
        $this->line('URL: '.($result['url'] ?? 'n/a'));
        $this->line('Text:');
        $this->line($result['excerpt'] ?: '(empty)');
    }

    return Command::SUCCESS;
})->purpose('Search messages in a Telegram channel using the Telegram client API.');
