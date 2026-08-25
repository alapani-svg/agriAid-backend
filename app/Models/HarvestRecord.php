<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HarvestRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'crop',
        'mass_kg',
        'quality_pct',
        'price_per_kg',
        'sell_on_market',
        'crop_image',
        'market_location',
        'asking_price_per_mt',
        'status',
        'harvest_date',
    ];

    protected $casts = [
        'mass_kg' => 'decimal:2',
        'quality_pct' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'sell_on_market' => 'boolean',
        'asking_price_per_mt' => 'integer',
        'harvest_date' => 'datetime',
    ];
}
