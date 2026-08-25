<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BuyerRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'buyerId' => $this->buyer_id,
            'farmerUserId' => $this->farmer_user_id,
            'buyerName' => $this->buyer?->name,
            'crop' => $this->crop,
            'quantityKg' => (float) $this->quantity_kg,
            'location' => $this->location,
            'deliveryDeadline' => $this->delivery_deadline?->toDateString(),
            'buyerMessage' => $this->buyer_message,
            'proposedPricePerKg' => $this->proposed_price_per_kg ? (float) $this->proposed_price_per_kg : null,
            'farmerMessage' => $this->farmer_message,
            'status' => $this->status,
            'rejectedBy' => $this->rejected_by,
            'rejectionReason' => $this->rejection_reason,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
