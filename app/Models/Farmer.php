<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    protected $fillable = [
        'user_id',
        'village',
        'region',
        'farm_size_hectares',
        'crop_types',
        'cig_group',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'crop_types' => 'array',
            'farm_size_hectares' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
