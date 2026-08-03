<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Harvest extends Model
{
    public const STATUSES = ['recorded', 'verified', 'in_transit'];

    protected $fillable = [
        'farmer_id',
        'crop',
        'mass_kg',
        'quality_pct',
        'price_per_kg',
        'status',
        'village',
        'region',
        'harvested_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'mass_kg' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'harvested_on' => 'date',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function estimatedValue(): ?float
    {
        if ($this->price_per_kg === null) {
            return null;
        }

        return (float) $this->mass_kg * (float) $this->price_per_kg;
    }
}
