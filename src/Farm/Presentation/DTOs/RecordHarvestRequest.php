<?php

namespace App\Farm\Presentation\DTOs;

readonly class RecordHarvestRequest
{
    public function __construct(
        public string $farmerId,
        public string $cropType,
        public float $quantityKg,
        public string $harvestDate,
        public ?float $qualityGrade = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            farmerId: $data['farmer_id'],
            cropType: $data['crop_type'],
            quantityKg: (float) $data['quantity_kg'],
            harvestDate: $data['harvest_date'],
            qualityGrade: isset($data['quality_grade']) ? (float) $data['quality_grade'] : null,
            notes: $data['notes'] ?? null,
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->farmerId)) {
            $errors['farmer_id'] = 'Farmer ID is required';
        }

        if (empty($this->cropType)) {
            $errors['crop_type'] = 'Crop type is required';
        }

        if ($this->quantityKg <= 0) {
            $errors['quantity_kg'] = 'Quantity must be positive';
        }

        if (empty($this->harvestDate)) {
            $errors['harvest_date'] = 'Harvest date is required';
        }

        if ($this->qualityGrade !== null && ($this->qualityGrade < 1.0 || $this->qualityGrade > 5.0)) {
            $errors['quality_grade'] = 'Quality grade must be between 1.0 and 5.0';
        }

        return $errors;
    }
}
