<?php

namespace App\Stock\Domain\Entities;

use App\Stock\Domain\ValueObjects\Capacity;
use App\Stock\Domain\ValueObjects\StockStatus;
use App\Farm\Domain\ValueObjects\CropType;
use App\Shared\Domain\AggregateRoot;
use App\Stock\Domain\Events\StockCreated;
use App\Stock\Domain\Events\StockReserved;
use App\Stock\Domain\Events\StockWithdrawn;
use App\Stock\Domain\Events\StockSold;

final class Stock extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private string $warehouseId,
        private ?string $harvestId,
        private CropType $cropType,
        private float $quantityKg,
        private Capacity $capacity,
        private \DateTimeImmutable $entryDate,
        private ?\DateTimeImmutable $exitDate,
        private StockStatus $status,
        private ?string $notes,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
        private ?string $photoPath = null,
        private ?float $aiEstimatedQuantityKg = null,
        private ?string $aiAnalysisNotes = null,
        private string $verificationStatus = 'unavailable',
    ) {}

    public static function create(
        string $id,
        string $warehouseId,
        ?string $harvestId,
        CropType $cropType,
        float $quantityKg,
        Capacity $capacity,
        \DateTimeImmutable $entryDate,
        ?string $notes = null,
    ): self {
        $stock = new self(
            id: $id,
            warehouseId: $warehouseId,
            harvestId: $harvestId,
            cropType: $cropType,
            quantityKg: $quantityKg,
            capacity: $capacity,
            entryDate: $entryDate,
            exitDate: null,
            status: StockStatus::IN_STOCK,
            notes: $notes,
            createdAt: new \DateTimeImmutable(),
        );

        $stock->recordEvent(new StockCreated($stock));

        return $stock;
    }

    public function reserve(): void
    {
        if (!$this->status->canBeReserved()) {
            throw new \DomainException('Stock cannot be reserved in current status');
        }

        $this->status = StockStatus::RESERVED;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new StockReserved($this));
    }

    public function withdraw(): void
    {
        if (!$this->status->canBeWithdrawn()) {
            throw new \DomainException('Stock cannot be withdrawn in current status');
        }

        $this->status = StockStatus::WITHDRAWN;
        $this->exitDate = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new StockWithdrawn($this));
    }

    public function sell(): void
    {
        if (!$this->status->canBeSold()) {
            throw new \DomainException('Stock cannot be sold in current status');
        }

        $this->status = StockStatus::SOLD;
        $this->exitDate = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new StockSold($this));
    }

    public function updateCapacity(Capacity $newCapacity): void
    {
        $this->capacity = $newCapacity;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Attaches the result of the AI photo-verification pass — comparing the
     * declared stock quantity against what a vision model can see in the
     * uploaded goods photo, to help keep declarations honest.
     */
    public function attachPhotoVerification(
        ?string $photoPath,
        ?float $aiEstimatedQuantityKg,
        ?string $aiAnalysisNotes,
        string $verificationStatus,
    ): void {
        $this->photoPath = $photoPath;
        $this->aiEstimatedQuantityKg = $aiEstimatedQuantityKg;
        $this->aiAnalysisNotes = $aiAnalysisNotes;
        $this->verificationStatus = $verificationStatus;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getWarehouseId(): string
    {
        return $this->warehouseId;
    }

    public function getHarvestId(): ?string
    {
        return $this->harvestId;
    }

    public function getCropType(): CropType
    {
        return $this->cropType;
    }

    public function getQuantityKg(): float
    {
        return $this->quantityKg;
    }

    public function getCapacity(): Capacity
    {
        return $this->capacity;
    }

    public function getEntryDate(): \DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function getExitDate(): ?\DateTimeImmutable
    {
        return $this->exitDate;
    }

    public function getStatus(): StockStatus
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function getAiEstimatedQuantityKg(): ?float
    {
        return $this->aiEstimatedQuantityKg;
    }

    public function getAiAnalysisNotes(): ?string
    {
        return $this->aiAnalysisNotes;
    }

    public function getVerificationStatus(): string
    {
        return $this->verificationStatus;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isInStock(): bool
    {
        return $this->status === StockStatus::IN_STOCK;
    }

    public function isReserved(): bool
    {
        return $this->status === StockStatus::RESERVED;
    }

    public function isSold(): bool
    {
        return $this->status === StockStatus::SOLD;
    }
}
