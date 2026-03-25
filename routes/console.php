<?php

use App\Models\Cpu;
use App\Services\CpuOffers\CpuOfferSyncService;
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
