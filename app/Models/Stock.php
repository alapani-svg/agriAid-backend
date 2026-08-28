<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    public const STATUSES = ['in_stock', 'reserved', 'withdrawn', 'sold'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'warehouse_id',
        'store_id',
        'harvest_id',
        'crop_type',
        'variety',
        'quantity_kg',
        'price_per_kg',
        'currency',
        'unit',
        'unit_weight_kg',
        'price_tiers',
        'quality_grade',
        'origin',
        'seller_id',
        'is_urgent_sale',
        'flash_discount_percent',
        'flash_discount_expires_at',
        'capacity_used',
        'capacity_total',
        'entry_date',
        'exit_date',
        'status',
        'notes',
        'photo_path',
        'ai_estimated_quantity_kg',
        'ai_analysis_notes',
        'verification_status',
        'validation_status',
        'validated_by',
        'validated_at',
        'validation_notes',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'unit_weight_kg' => 'decimal:2',
            'price_tiers' => 'array',
            'is_urgent_sale' => 'boolean',
            'flash_discount_percent' => 'decimal:2',
            'flash_discount_expires_at' => 'datetime',
            'capacity_used' => 'decimal:2',
            'capacity_total' => 'decimal:2',
            'entry_date' => 'date',
            'exit_date' => 'date',
            'ai_estimated_quantity_kg' => 'decimal:2',
            'validated_at' => 'datetime',
        ];
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Farmer::class, 'seller_id');
    }
}
