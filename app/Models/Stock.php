<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'farmer_id',
        'crop',
        'quantity_kg',
        'unit',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
