<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HarvestRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'crop' => $this->crop,
            'massKg' => (float) $this->mass_kg,
            'qualityPct' => (float) $this->quality_pct,
            'pricePerKg' => $this->price_per_kg ? (float) $this->price_per_kg : null,
            'dateRecorded' => $this->harvest_date?->diffForHumans() ?? 'Just now',
            'verifiedBy' => $this->status === 'VERIFIED' ? 'Silo IoT Intake Node' : 'Awaiting IoT Check',
            'status' => $this->status,
            'estimatedValueFcfa' => $this->price_per_kg ? (float) round($this->mass_kg * $this->price_per_kg, 2) : null,
        ];
    }
}
