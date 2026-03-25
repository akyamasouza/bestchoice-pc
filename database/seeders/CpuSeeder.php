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
                'sku' => '100-100000719WOF',
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
                    'l3' => '144 MB',
                ],
                'benchmark' => [
                    'multithread_rating' => 70212,
                    'single_thread_rating' => 4742,
                    'samples' => 8854,
                    'margin_for_error' => 'Low',
                ],
                'first_seen' => 'Q1 2025',
                'store_urls' => [
                    'amazon' => 'https://www.amazon.com.br/Processador-AMD-Ryzen-9950X3D-Graphics/dp/B0DVZSG8D5/ref=asc_df_B0DVZSG8D5?mcid=a637ac5e095a30eaad94fbebe114bc7b&hvadid=709884378235&hvpos=&hvnetw=g&hvrand=1308529211578722872&hvpone=&hvptwo=&hvqmt=&hvdev=c&hvdvcmdl=&hvlocint=&hvlocphy=9101722&hvtargid=pla-2408213641585&psc=1&hvocijid=1308529211578722872-B0DVZSG8D5-&hvexpln=0&language=pt_BR',
                    'kabum' => 'https://www.kabum.com.br/produto/708039/processador-amd-ryzen-9-9950x3d-4-4-ghz-max-boos-clock-ate-5-5-ghz-cache-144mb-16-nucleos-threads-32-am5-100-100000719wof',
                    'pichau' => 'https://www.pichau.com.br/processador-amd-ryzen-9-9950x3d-16-core-32-threads-4-3ghz-5-7ghz-turbo-cache-144mb-am5-100-100000719wof-br',
                    'terabyteshop' => 'https://www.terabyteshop.com.br/produto/34738/processador-amd-ryzen-9-9950x3d-43ghz-57ghz-turbo-16-cores-32-threads-am5-sem-cooler-100-100000719wof',
                ],
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
