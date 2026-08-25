<?php

namespace App\Farmer\Domain\Entities;

use App\Farmer\Domain\ValueObjects\CropTypes;
use App\Farmer\Domain\ValueObjects\FarmSize;
use App\Farmer\Domain\ValueObjects\FarmerStatus;
use App\Farmer\Domain\ValueObjects\Region;
use App\Shared\Domain\AggregateRoot;
use App\Farmer\Domain\Events\FarmerRegistered;
use App\Farmer\Domain\Events\FarmerProfileUpdated;
use App\Farmer\Domain\Events\FarmerStatusChanged;

final class Farmer extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private string $userId,
        private string $farmName,
        private FarmSize $farmSize,
        private CropTypes $crops,
        private Region $region,
        private string $village,
        private ?string $phone,
        private ?string $address,
        private ?string $cooperativeName,
        private ?string $cooperativeId,
        private FarmerStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public static function register(
        string $id,
        string $userId,
        string $farmName,
        FarmSize $farmSize,
        CropTypes $crops,
        Region $region,
        string $village,
        ?string $phone = null,
        ?string $address = null,
        ?string $cooperativeName = null,
        ?string $cooperativeId = null,
    ): self {
        $farmer = new self(
            id: $id,
            userId: $userId,
            farmName: $farmName,
            farmSize: $farmSize,
            crops: $crops,
            region: $region,
            village: $village,
            phone: $phone,
            address: $address,
            cooperativeName: $cooperativeName,
            cooperativeId: $cooperativeId,
            status: FarmerStatus::ACTIVE,
            createdAt: new \DateTimeImmutable(),
        );

        $farmer->recordEvent(new FarmerRegistered($farmer));

        return $farmer;
    }

    public function updateProfile(
        ?string $farmName = null,
        ?FarmSize $farmSize = null,
        ?CropTypes $crops = null,
        ?Region $region = null,
        ?string $village = null,
        ?string $phone = null,
        ?string $address = null,
        ?string $cooperativeName = null,
        ?string $cooperativeId = null,
    ): void {
        if ($farmName !== null) {
            $this->farmName = $farmName;
        }
        if ($farmSize !== null) {
            $this->farmSize = $farmSize;
        }
        if ($crops !== null) {
            $this->crops = $crops;
        }
        if ($region !== null) {
            $this->region = $region;
        }
        if ($village !== null) {
            $this->village = $village;
        }
        if ($phone !== null) {
            $this->phone = $phone;
        }
        if ($address !== null) {
            $this->address = $address;
        }
        if ($cooperativeName !== null) {
            $this->cooperativeName = $cooperativeName;
        }
        if ($cooperativeId !== null) {
            $this->cooperativeId = $cooperativeId;
        }

        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new FarmerProfileUpdated($this));
    }

    public function activate(): void
    {
        if ($this->status === FarmerStatus::ACTIVE) {
            return;
        }

        $this->status = FarmerStatus::ACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new FarmerStatusChanged($this, FarmerStatus::ACTIVE));
    }

    public function suspend(): void
    {
        if ($this->status === FarmerStatus::SUSPENDED) {
            return;
        }

        $this->status = FarmerStatus::SUSPENDED;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new FarmerStatusChanged($this, FarmerStatus::SUSPENDED));
    }

    public function deactivate(): void
    {
        if ($this->status === FarmerStatus::INACTIVE) {
            return;
        }

        $this->status = FarmerStatus::INACTIVE;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new FarmerStatusChanged($this, FarmerStatus::INACTIVE));
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getFarmName(): string
    {
        return $this->farmName;
    }

    public function getFarmSize(): FarmSize
    {
        return $this->farmSize;
    }

    public function getCrops(): CropTypes
    {
        return $this->crops;
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function getVillage(): string
    {
        return $this->village;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCooperativeName(): ?string
    {
        return $this->cooperativeName;
    }

    public function getCooperativeId(): ?string
    {
        return $this->cooperativeId;
    }

    public function getStatus(): FarmerStatus
    {
        return $this->status;
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
        return $this->status === FarmerStatus::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === FarmerStatus::SUSPENDED;
    }
}
