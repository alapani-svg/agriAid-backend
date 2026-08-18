<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->order_number,
            'commodity' => $this->commodity,
            'lastOfferFcfaPerMt' => (float) $this->price_fcfa_per_mt,
            'status' => $this->status ?? 'PENDING',
        ];
    }
}
