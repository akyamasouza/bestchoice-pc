<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable([
    'name',
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
        ];
    }
}
