<?php

namespace App\Store\Application\Services;

use App\Models\Stock as EloquentStock;
use App\Store\Domain\Entities\StoreOrder;
use App\Store\Domain\Repositories\StoreOrderRepositoryInterface;
use App\Store\Presentation\DTOs\CreateStoreOrderRequest;
use Illuminate\Support\Str;

class CreateStoreOrderService
{
    public function __construct(
        private readonly StoreOrderRepositoryInterface $repository
    ) {}

    public function execute(CreateStoreOrderRequest $dto, string $buyerId): StoreOrder
    {
        $stock = EloquentStock::find($dto->stockId);

        if ($stock === null) {
            throw new \DomainException('Stock not found');
        }

        if ($stock->status !== 'in_stock') {
            throw new \DomainException('Stock is not available for order');
        }

        if ((float) $stock->quantity_kg < $dto->quantityKg) {
            throw new \DomainException('Order quantity exceeds available stock');
        }

        $basePrice = (float) ($stock->price_per_kg ?? 0);
        $isUrgent = (bool) $stock->is_urgent_sale;
        $flashActive = $isUrgent
            && (float) $stock->flash_discount_percent > 0
            && ($stock->flash_discount_expires_at === null || now()->isBefore($stock->flash_discount_expires_at));
        $discount = $flashActive ? (float) $stock->flash_discount_percent : 0;
        $effectivePrice = $basePrice * (1 - $discount / 100);

        $pricePerKg = $dto->pricePerKg ?? ($effectivePrice > 0 ? $effectivePrice : null);
        $totalAmount = $pricePerKg !== null ? round($dto->quantityKg * $pricePerKg, 2) : null;

        $order = StoreOrder::create(
            id: (string) Str::uuid(),
            stockId: $dto->stockId,
            buyerId: $buyerId,
            quantityKg: $dto->quantityKg,
            pricePerKg: $pricePerKg,
            totalAmount: $totalAmount,
            notes: $dto->notes,
        );

        $this->repository->save($order);

        $stock->status = 'reserved';
        $stock->save();

        return $order;
    }
}
