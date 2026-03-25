<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable([
    'name',
    'sku',
    'other_names',
    'description',
    'class',
    'socket',
    'clockspeed_ghz',
    'turbo_speed_ghz',
    'cores',
    'threads',
    'typical_tdp_w',
    'cache',
    'benchmark',
    'first_seen',
    'store_urls',
])]
class Cpu extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'cpus';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'clockspeed_ghz' => 'float',
            'turbo_speed_ghz' => 'float',
            'cores' => 'integer',
            'threads' => 'integer',
            'typical_tdp_w' => 'integer',
            'cache' => 'array',
            'benchmark' => 'array',
            'store_urls' => 'array',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(CpuOffer::class, 'cpu_id');
    }
}
