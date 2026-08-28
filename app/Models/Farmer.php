<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'farm_name',
        'farm_size',
        'crops',
        'region',
        'village',
        'phone',
        'address',
        'cooperative_name',
        'cooperative_id',
        'status',
        'verified',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'crops' => 'array',
            'farm_size' => 'decimal:2',
            'verified' => 'boolean',
            'verified_at' => 'datetime',
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
}
