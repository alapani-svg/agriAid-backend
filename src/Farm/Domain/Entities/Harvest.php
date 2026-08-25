<?php

namespace App\Farm\Domain\Entities;

use App\Farm\Domain\ValueObjects\CropType;
use App\Farm\Domain\ValueObjects\HarvestStatus;
use App\Farm\Domain\ValueObjects\QualityGrade;
use App\Farm\Domain\ValueObjects\Quantity;
use App\Shared\Domain\AggregateRoot;
use App\Farm\Domain\Events\HarvestRecorded;
use App\Farm\Domain\Events\HarvestStatusChanged;
use App\Farm\Domain\Events\HarvestStored;

final class Harvest extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private string $farmerId,
        private ?string $warehouseId,
        private CropType $cropType,
        private Quantity $quantity,
        private ?QualityGrade $qualityGrade,
        private \DateTimeImmutable $harvestDate,
        private ?\DateTimeImmutable $storageDate,
        private HarvestStatus $status,
        private ?string $notes,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
        private ?string $photoPath = null,
        private ?float $aiEstimatedQuantityKg = null,
        private ?string $aiAnalysisNotes = null,
        private string $verificationStatus = 'unavailable',
    ) {}

    public static function record(
        string $id,
        string $farmerId,
        CropType $cropType,
        Quantity $quantity,
        \DateTimeImmutable $harvestDate,
        ?QualityGrade $qualityGrade = null,
        ?string $notes = null,
    ): self {
        $harvest = new self(
            id: $id,
            farmerId: $farmerId,
            warehouseId: null,
            cropType: $cropType,
            quantity: $quantity,
            qualityGrade: $qualityGrade,
            harvestDate: $harvestDate,
            storageDate: null,
            status: HarvestStatus::HARVESTED,
            notes: $notes,
            createdAt: new \DateTimeImmutable(),
        );

        $harvest->recordEvent(new HarvestRecorded($harvest));

        return $harvest;
    }

    /**
     * Attaches the result of the AI photo-verification pass — comparing the
     * farmer's declared quantity against what a vision model can see in the
     * uploaded harvest photo, to help keep declarations honest.
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

    public function sendToWarehouse(string $warehouseId): void
    {
        if (!$this->status->canBeStored()) {
            throw new \DomainException('Harvest cannot be sent to warehouse in current status');
        }

        $this->warehouseId = $warehouseId;
        $this->status = HarvestStatus::IN_TRANSIT;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new HarvestStatusChanged($this, HarvestStatus::IN_TRANSIT));
    }

    public function storeInWarehouse(): void
    {
        if (!$this->status->canBeStored()) {
            throw new \DomainException('Harvest cannot be stored in current status');
        }

        $this->status = HarvestStatus::STORED;
        $this->storageDate = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new HarvestStored($this));
    }

    public function markAsSold(): void
    {
        if (!$this->status->canBeSold()) {
            throw new \DomainException('Harvest cannot be sold in current status');
        }

        $this->status = HarvestStatus::SOLD;
        $this->updatedAt = new \DateTimeImmutable();
        $this->recordEvent(new HarvestStatusChanged($this, HarvestStatus::SOLD));
    }

    public function updateQualityGrade(QualityGrade $grade): void
    {
        $this->qualityGrade = $grade;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateNotes(?string $notes): void
    {
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getFarmerId(): string
    {
        return $this->farmerId;
    }

    public function getWarehouseId(): ?string
    {
        return $this->warehouseId;
    }

    public function getCropType(): CropType
    {
        return $this->cropType;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getQualityGrade(): ?QualityGrade
    {
        return $this->qualityGrade;
    }

    public function getHarvestDate(): \DateTimeImmutable
    {
        return $this->harvestDate;
    }

    public function getStorageDate(): ?\DateTimeImmutable
    {
        return $this->storageDate;
    }

    public function getStatus(): HarvestStatus
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

    public function isStored(): bool
    {
        return $this->status === HarvestStatus::STORED;
    }

    public function isInTransit(): bool
    {
        return $this->status === HarvestStatus::IN_TRANSIT;
    }

    public function isSold(): bool
    {
        return $this->status === HarvestStatus::SOLD;
    }
}
