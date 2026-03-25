<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable([
    'cpu_id',
    'cpu_name',
    'cpu_sku',
    'store',
    'url',
    'product_name',
    'seller',
    'status',
    'is_available',
    'currency',
    'list_price',
    'price_pix',
    'price_card',
    'installments',
    'installment_price',
    'discount_percent',
    'checked_at',
    'meta',
])]
class CpuOffer extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'cpu_offers';

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'list_price' => 'float',
            'price_pix' => 'float',
            'price_card' => 'float',
            'installments' => 'integer',
            'installment_price' => 'float',
            'discount_percent' => 'integer',
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function cpu(): BelongsTo
    {
        return $this->belongsTo(Cpu::class, 'cpu_id');
    }
}
