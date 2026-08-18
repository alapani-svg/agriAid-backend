<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'commodity',
        'symbol',
        'city',
        'hub',
        'price_fcfa_per_kg',
        'price_usd_per_kg',
        'price_fcfa_per_mt',
        'price_usd_per_mt',
        'trend',
        'change_percent',
    ];

    protected $casts = [
        'price_fcfa_per_kg' => 'decimal:2',
        'price_usd_per_kg' => 'decimal:4',
        'price_fcfa_per_mt' => 'decimal:2',
        'price_usd_per_mt' => 'decimal:2',
        'change_percent' => 'decimal:2',
    ];
}
