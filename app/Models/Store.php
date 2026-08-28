<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Store extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'warehouse_id',
        'farmer_id',
        'name',
        'slug',
        'description',
        'banner_path',
        'logo_path',
        'status',
        'theme_color',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name) . '-' . substr((string) Str::uuid(), 0, 8);
            }
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function products(): HasMany
    {
        return $this->stocks();
    }
}
