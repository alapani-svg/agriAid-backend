<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Harvest extends Model
{
    public const STATUSES = ['harvested', 'in_transit', 'stored', 'sold'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'farmer_id',
        'warehouse_id',
        'crop_type',
        'quantity_kg',
        'quality_grade',
        'harvest_date',
        'storage_date',
        'status',
        'notes',
        'photo_path',
        'ai_estimated_quantity_kg',
        'ai_analysis_notes',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'quality_grade' => 'decimal:2',
            'harvest_date' => 'date',
            'storage_date' => 'date',
            'ai_estimated_quantity_kg' => 'decimal:2',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
