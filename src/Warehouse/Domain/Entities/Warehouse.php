<?php

namespace App\Warehouse\Domain\Entities;

use App\Shared\Domain\AggregateRoot;
use App\Warehouse\Domain\Events\WarehouseRegistered;
use App\Warehouse\Domain\ValueObjects\WarehouseStatus;

final class Warehouse extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private ?string $managerUserId,
        private ?string $farmerId,
        private string $name,
        private string $region,
        private ?string $village,
        private ?string $address,
        private float $capacityTotalKg,
        private WarehouseStatus $status,
        private ?string $notes,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
        private bool $aerationActive = false,
        private ?\DateTimeImmutable $aerationUpdatedAt = null,
    ) {}

    public static function register(
        string $id,
        string $name,
        string $region,
        float $capacityTotalKg,
        ?string $managerUserId = null,
        ?string $farmerId = null,
        ?string $village = null,
        ?string $address = null,
        ?string $notes = null,
    ): self {
        if ($capacityTotalKg <= 0) {
            throw new \InvalidArgumentException('Warehouse total capacity must be positive');
        }

        $warehouse = new self(
            id: $id,
            managerUserId: $managerUserId,
            farmerId: $farmerId,
            name: $name,
            region: $region,
            village: $village,
            address: $address,
            capacityTotalKg: $capacityTotalKg,
            status: WarehouseStatus::ACTIVE,
            notes: $notes,
            createdAt: new \DateTimeImmutable(),
        );

        $warehouse->recordEvent(new WarehouseRegistered($warehouse));

        return $warehouse;
    }

    /**
     * Toggles the aeration system on/off. This is a real, persisted operational
     * control (a manager-triggered switch) — not connected to physical hardware.
     */
    public function setAerationActive(bool $active): void
    {
        $this->aerationActive = $active;
        $this->aerationUpdatedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Validates whether this warehouse can accept an additional quantity of stock,
     * given the amount currently held.
     */
    public function canAccept(float $currentlyUsedKg, float $additionalKg): bool
    {
        if (!$this->status->canReceiveStock()) {
            return false;
        }

        return ($currentlyUsedKg + $additionalKg) <= $this->capacityTotalKg;
    }

    public function getAvailableCapacity(float $currentlyUsedKg): float
    {
        return max(0.0, $this->capacityTotalKg - $currentlyUsedKg);
    }

    public function updateCapacity(float $capacityTotalKg): void
    {
        if ($capacityTotalKg <= 0) {
            throw new \InvalidArgumentException('Warehouse total capacity must be positive');
        }

        $this->capacityTotalKg = $capacityTotalKg;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Assigns (or clears, when null) the user responsible for managing this
     * warehouse. Called by an administrator once a warehouse-role account
     * has been created for that manager.
     */
    public function assignManager(?string $managerUserId): void
    {
        $this->managerUserId = $managerUserId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Assigns (or clears, when null) the farmer who owns the produce stored
     * in this warehouse. The farmer is the true owner; the warehouse manager
     * is an operational role assigned separately.
     */
    public function assignFarmer(?string $farmerId): void
    {
        $this->farmerId = $farmerId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->status = WarehouseStatus::INACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->status = WarehouseStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateDetails(
        string $name,
        string $region,
        ?string $village,
        ?string $address,
        ?string $notes,
    ): void {
        $this->name = $name;
        $this->region = $region;
        $this->village = $village;
        $this->address = $address;
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getManagerUserId(): ?string
    {
        return $this->managerUserId;
    }

    public function getFarmerId(): ?string
    {
        return $this->farmerId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getVillage(): ?string
    {
        return $this->village;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCapacityTotalKg(): float
    {
        return $this->capacityTotalKg;
    }

    public function getStatus(): WarehouseStatus
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === WarehouseStatus::ACTIVE;
    }

    public function isAerationActive(): bool
    {
        return $this->aerationActive;
    }

    public function getAerationUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->aerationUpdatedAt;
    }

    /**
     * Restores aeration state when rehydrating from persistence, without
     * touching updatedAt (unlike setAerationActive, which is a real mutation).
     */
    public function hydrateAerationState(bool $active, ?\DateTimeImmutable $aerationUpdatedAt): void
    {
        $this->aerationActive = $active;
        $this->aerationUpdatedAt = $aerationUpdatedAt;
    }
}
