<?php

namespace App\Farm\Application\Services;

use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Domain\ValueObjects\CropType;
use App\Farm\Domain\ValueObjects\QualityGrade;
use App\Farm\Domain\ValueObjects\Quantity;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Domain\Exceptions\FarmerNotFoundException;
use Illuminate\Support\Str;

class RecordHarvestService
{
    public function __construct(
        private readonly HarvestRepositoryInterface $harvestRepository,
        private readonly FarmerRepositoryInterface $farmerRepository
    ) {}

    public function execute(
        string $farmerId,
        string $cropType,
        float $quantityKg,
        string $harvestDate,
        ?float $qualityGrade = null,
        ?string $notes = null,
    ): Harvest {
        // Verify farmer exists
        $farmer = $this->farmerRepository->findByUserId($farmerId);
        if ($farmer === null) {
            throw new FarmerNotFoundException("Farmer not found: {$farmerId}");
        }

        // Verify farmer is active
        if (!$farmer->isActive()) {
            throw new \DomainException("Farmer is not active and cannot record harvests");
        }

        $harvest = Harvest::record(
            id: (string) Str::uuid(),
            farmerId: $farmerId,
            cropType: CropType::fromString($cropType),
            quantity: Quantity::fromKilograms($quantityKg),
            harvestDate: new \DateTimeImmutable($harvestDate),
            qualityGrade: $qualityGrade !== null ? QualityGrade::fromScore($qualityGrade) : null,
            notes: $notes,
        );

        $this->harvestRepository->save($harvest);

        return $harvest;
    }
}
