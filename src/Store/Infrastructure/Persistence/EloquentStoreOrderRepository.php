<?php

namespace App\Store\Infrastructure\Persistence;

use App\Models\StoreOrder as EloquentStoreOrder;
use App\Store\Domain\Entities\StoreOrder;
use App\Store\Domain\Repositories\StoreOrderRepositoryInterface;
use App\Store\Domain\ValueObjects\StoreOrderStatus;

class EloquentStoreOrderRepository implements StoreOrderRepositoryInterface
{
    public function save(StoreOrder $order): void
    {
        $eloquent = EloquentStoreOrder::query()
            ->where('id', $order->getId())
            ->first();

        if ($eloquent === null) {
            $eloquent = new EloquentStoreOrder();
            $eloquent->id = $order->getId();
        }

        $eloquent->stock_id = $order->getStockId();
        $eloquent->buyer_id = $order->getBuyerId();
        $eloquent->quantity_kg = $order->getQuantityKg();
        $eloquent->price_per_kg = $order->getPricePerKg();
        $eloquent->total_amount = $order->getTotalAmount();
        $eloquent->status = $order->getStatus()->toString();
        $eloquent->notes = $order->getNotes();
        $eloquent->delivery_method = $order->getDeliveryMethod();
        $eloquent->delivery_address = $order->getDeliveryAddress();
        $eloquent->delivery_city = $order->getDeliveryCity();
        $eloquent->delivery_phone = $order->getDeliveryPhone();
        $eloquent->delivery_notes = $order->getDeliveryNotes();
        $eloquent->created_at = $order->getCreatedAt()->format('Y-m-d H:i:s');
        $eloquent->updated_at = $order->getUpdatedAt()?->format('Y-m-d H:i:s');

        $eloquent->save();
    }

    public function findById(string $id): ?StoreOrder
    {
        $eloquent = EloquentStoreOrder::find($id);

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function findByBuyerId(string $buyerId): array
    {
        return EloquentStoreOrder::where('buyer_id', $buyerId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($eloquent) => $this->toDomain($eloquent))
            ->toArray();
    }

    public function findByStockId(string $stockId): array
    {
        return EloquentStoreOrder::where('stock_id', $stockId)
            ->get()
            ->map(fn ($eloquent) => $this->toDomain($eloquent))
            ->toArray();
    }

    public function findAll(): array
    {
        return EloquentStoreOrder::orderByDesc('created_at')
            ->get()
            ->map(fn ($eloquent) => $this->toDomain($eloquent))
            ->toArray();
    }

    public function delete(StoreOrder $order): void
    {
        EloquentStoreOrder::where('id', $order->getId())->delete();
    }

    private function toDomain(EloquentStoreOrder $eloquent): StoreOrder
    {
        $order = StoreOrder::create(
            id: $eloquent->id,
            stockId: $eloquent->stock_id,
            buyerId: $eloquent->buyer_id,
            quantityKg: (float) $eloquent->quantity_kg,
            pricePerKg: $eloquent->price_per_kg !== null ? (float) $eloquent->price_per_kg : null,
            totalAmount: $eloquent->total_amount !== null ? (float) $eloquent->total_amount : null,
            notes: $eloquent->notes,
            deliveryMethod: $eloquent->delivery_method,
            deliveryAddress: $eloquent->delivery_address,
            deliveryPhone: $eloquent->delivery_phone,
            deliveryCity: $eloquent->delivery_city,
            deliveryNotes: $eloquent->delivery_notes,
        );

        $status = $eloquent->status;
        if ($status === StoreOrderStatus::FARMER_CONFIRMED->value) {
            $order->farmerConfirm();
        } elseif ($status === StoreOrderStatus::CONFIRMED->value) {
            $order->confirm();
        } elseif ($status === StoreOrderStatus::SHIPPED->value) {
            $order->ship();
        } elseif ($status === StoreOrderStatus::DELIVERED->value) {
            $order->deliver();
        } elseif ($status === StoreOrderStatus::COMPLETED->value) {
            $order->complete();
        } elseif ($status === StoreOrderStatus::CANCELLED->value) {
            $order->cancel();
        }

        return $order;
    }
}
