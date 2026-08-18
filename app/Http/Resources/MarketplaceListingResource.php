<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceListingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'qtyMt' => (float) $this->qty_mt,
            'location' => $this->location,
            'askingPricePerMtFcfa' => (float) $this->price_fcfa_per_mt,
            'imageUrl' => $this->image_url,
            'verified' => (bool) $this->verified,
            'estateReserve' => (bool) $this->estate_reserve,
        ];
    }
}
