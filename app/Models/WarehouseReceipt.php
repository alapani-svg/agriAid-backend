<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseReceipt extends Model
{
    public const STATUSES = ['active', 'redeemed', 'cancelled'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'receipt_number',
        'warehouse_id',
        'stock_id',
        'farmer_id',
        'crop_type',
        'quantity_kg',
        'issue_date',
        'qr_code_data',
        'integrity_hash',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:2',
            'issue_date' => 'date',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }
}
