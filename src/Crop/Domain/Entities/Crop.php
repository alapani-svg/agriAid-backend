<?php

namespace Src\Crop\Domain\Entities;

use Src\Shared\Contracts\AggregateRoot;
use Src\Crop\Domain\Events\CropPlanted;
use Src\Crop\Domain\ValueObjects\CropType;
use Src\Crop\Domain\ValueObjects\PlantingDate;
use Src\Crop\Domain\ValueObjects\ExpectedHarvestDate;

class Crop extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private readonly string $farmId,
        private CropType $type,
        private PlantingDate $plantingDate,
        private ?ExpectedHarvestDate $expectedHarvestDate,
        private string $status,
        private array $metadata,
    ) {}

    public static function plant(
        string $id,
        string $farmId,
        CropType $type,
        PlantingDate $plantingDate,
        ?ExpectedHarvestDate $expectedHarvestDate = null,
        array $metadata = [],
    ): self {
        $crop = new self(
            $id,
            $farmId,
            $type,
            $plantingDate,
            $expectedHarvestDate,
            'planted',
            $metadata,
        );

        $crop->recordEvent(new CropPlanted($crop));

        return $crop;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFarmId(): string
    {
        return $this->farmId;
    }

    public function getType(): CropType
    {
        return $this->type;
    }

    public function getPlantingDate(): PlantingDate
    {
        return $this->plantingDate;
    }

    public function getExpectedHarvestDate(): ?ExpectedHarvestDate
    {
        return $this->expectedHarvestDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
    }

    public function updateMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }
}
