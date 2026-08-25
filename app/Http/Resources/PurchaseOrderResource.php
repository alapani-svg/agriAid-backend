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
            'quantityMt' => (int) $this->quantity_mt,
            'lastOfferFcfaPerMt' => (float) $this->price_fcfa_per_mt,
            'totalFcfa' => (float) $this->total_fcfa,
            'paymentMethod' => $this->payment_method,
            'deliveryCity' => $this->delivery_city,
            'deliveryStatus' => $this->delivery_status ?? 'NOT SHIPPED',
            'paymentStatus' => $this->payment_status ?? 'PENDING',
            'status' => $this->status ?? 'PENDING',
        ];
    }
}
