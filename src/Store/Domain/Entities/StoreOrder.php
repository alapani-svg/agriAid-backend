<?php

namespace App\Store\Domain\Entities;

use App\Shared\Domain\AggregateRoot;
use App\Store\Domain\Events\StoreOrderCreated;
use App\Store\Domain\ValueObjects\StoreOrderStatus;

final class StoreOrder extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private string $stockId,
        private string $buyerId,
        private float $quantityKg,
        private ?float $pricePerKg,
        private ?float $totalAmount,
        private StoreOrderStatus $status,
        private ?string $notes,
        private ?string $deliveryMethod,
        private ?string $deliveryAddress,
        private ?string $deliveryPhone,
        private ?string $deliveryCity,
        private ?string $deliveryNotes,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public static function create(
        string $id,
        string $stockId,
        string $buyerId,
        float $quantityKg,
        ?float $pricePerKg = null,
        ?float $totalAmount = null,
        ?string $notes = null,
        ?string $deliveryMethod = null,
        ?string $deliveryAddress = null,
        ?string $deliveryPhone = null,
        ?string $deliveryCity = null,
        ?string $deliveryNotes = null,
    ): self {
        $order = new self(
            id: $id,
            stockId: $stockId,
            buyerId: $buyerId,
            quantityKg: $quantityKg,
            pricePerKg: $pricePerKg,
            totalAmount: $totalAmount,
            status: StoreOrderStatus::PENDING,
            notes: $notes,
            deliveryMethod: $deliveryMethod,
            deliveryAddress: $deliveryAddress,
            deliveryPhone: $deliveryPhone,
            deliveryCity: $deliveryCity,
            deliveryNotes: $deliveryNotes,
            createdAt: new \DateTimeImmutable(),
        );

        $order->recordEvent(new StoreOrderCreated($order));

        return $order;
    }

    public function confirm(): void
    {
        $this->status = StoreOrderStatus::CONFIRMED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function farmerConfirm(): void
    {
        $this->status = StoreOrderStatus::FARMER_CONFIRMED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function ship(): void
    {
        $this->status = StoreOrderStatus::SHIPPED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deliver(): void
    {
        $this->status = StoreOrderStatus::DELIVERED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        $this->status = StoreOrderStatus::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        $this->status = StoreOrderStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getStockId(): string
    {
        return $this->stockId;
    }

    public function getBuyerId(): string
    {
        return $this->buyerId;
    }

    public function getQuantityKg(): float
    {
        return $this->quantityKg;
    }

    public function getPricePerKg(): ?float
    {
        return $this->pricePerKg;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function getStatus(): StoreOrderStatus
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getDeliveryMethod(): ?string
    {
        return $this->deliveryMethod;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function getDeliveryPhone(): ?string
    {
        return $this->deliveryPhone;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function getDeliveryNotes(): ?string
    {
        return $this->deliveryNotes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
