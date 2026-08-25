<?php

namespace App\Store\Application\Services;

use App\Models\Stock as EloquentStock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class ListAvailableStoreStockService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(): array
    {
        /** @var Collection $stocks */
        $stocks = EloquentStock::with(['harvest.farmer', 'warehouse', 'seller'])
            ->where('status', 'in_stock')
            ->where('quantity_kg', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return $stocks->map(function ($stock) {
            $basePrice = (float) ($stock->price_per_kg ?? 0);
            $isUrgent = (bool) $stock->is_urgent_sale;
            $flashActive = $isUrgent
                && $stock->flash_discount_percent > 0
                && ($stock->flash_discount_expires_at === null || now()->isBefore($stock->flash_discount_expires_at));
            $discount = $flashActive ? (float) $stock->flash_discount_percent : 0;
            $effectivePrice = $basePrice * (1 - $discount / 100);

            $photoPath = $stock->photo_path ?: $stock->harvest?->photo_path;

            $seller = $stock->seller ?? $stock->harvest?->farmer;
            $sellerName = $seller?->farm_name ?? $seller?->user?->name ?? 'Unverified seller';
            $sellerCooperative = $seller?->cooperative_name;
            $sellerStatus = $seller?->status ?? 'pending';

            $harvestDate = $stock->harvest?->harvest_date ?? $stock->entry_date;
            $freshness = $this->freshnessFor($harvestDate);

            return [
                'id' => $stock->id,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse?->name,
                'warehouse_region' => $stock->warehouse?->region,
                'harvest_id' => $stock->harvest_id,
                'crop_type' => $stock->crop_type,
                'variety' => $stock->variety ?? $stock->crop_type,
                'quantity_kg' => (float) $stock->quantity_kg,
                'base_price_per_kg' => $basePrice,
                'effective_price_per_kg' => $effectivePrice,
                'currency' => $stock->currency ?? 'FCFA',
                'unit' => $stock->unit ?? 'kg',
                'unit_weight_kg' => $stock->unit_weight_kg !== null ? (float) $stock->unit_weight_kg : null,
                'price_tiers' => $stock->price_tiers ?? [],
                'quality_grade' => $stock->quality_grade,
                'origin' => $stock->origin,
                'seller_id' => $stock->seller_id,
                'seller_name' => $sellerName,
                'seller_cooperative' => $sellerCooperative,
                'seller_status' => $sellerStatus,
                'is_urgent_sale' => $isUrgent,
                'flash_discount_percent' => $discount,
                'flash_discount_expires_at' => $stock->flash_discount_expires_at?->format('Y-m-d H:i:s'),
                'freshness' => $freshness,
                'harvest_date' => $harvestDate?->format('Y-m-d'),
                'entry_date' => $stock->entry_date ? $stock->entry_date->format('Y-m-d') : null,
                'notes' => $stock->notes,
                'photo_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
                'ai_estimated_quantity_kg' => $stock->ai_estimated_quantity_kg !== null ? (float) $stock->ai_estimated_quantity_kg : null,
                'ai_analysis_notes' => $stock->ai_analysis_notes,
                'verification_status' => $stock->verification_status ?? 'unavailable',
                'created_at' => $stock->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $stock->updated_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    private function freshnessFor(?\DateTimeInterface $date): string
    {
        if ($date === null) {
            return 'unknown';
        }

        $days = (int) now()->diffInDays($date);

        return match (true) {
            $days <= 1 => 'high',
            $days <= 3 => 'medium',
            $days <= 7 => 'low',
            default => 'expiring',
        };
    }
}
