<?php

namespace Database\Seeders;

use App\Models\Cpu;
use Illuminate\Database\Seeder;

class CpuSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $cpus = [
            [
                'name' => 'AMD Ryzen 9 9950X3D',
                'other_names' => 'AMD Ryzen 9 9950X3D 16-Core Processor',
                'description' => 'AMD Radeon Graphics',
                'class' => 'Desktop',
                'socket' => 'AM5',
                'clockspeed_ghz' => 4.3,
                'turbo_speed_ghz' => 5.7,
                'cores' => 16,
                'threads' => 32,
                'typical_tdp_w' => 170,
                'cache' => [
                    'l1_instruction' => '16 x 32 KB',
                    'l1_data' => '16 x 48 KB',
                    'l2' => '16 x 1024 KB',
                    'l3' => '128 MB',
                ],
                'benchmark' => [
                    'multithread_rating' => 70212,
                    'single_thread_rating' => 4742,
                    'samples' => 8854,
                    'margin_for_error' => 'Low',
                ],
                'first_seen' => 'Q1 2025',
            ],
        ];

        foreach ($cpus as $cpu) {
            Cpu::query()->updateOrCreate(
                ['name' => $cpu['name']],
                $cpu,
            );
        }
    }
}
