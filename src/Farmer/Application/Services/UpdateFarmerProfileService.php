<?php

namespace App\Farmer\Application\Services;

use App\Farmer\Domain\Entities\Farmer;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Domain\ValueObjects\CropTypes;
use App\Farmer\Domain\ValueObjects\FarmSize;
use App\Farmer\Domain\ValueObjects\Region;
use App\Farmer\Domain\Exceptions\FarmerNotFoundException;

class UpdateFarmerProfileService
{
    public function __construct(
        private readonly FarmerRepositoryInterface $farmerRepository
    ) {}

    public function execute(
        string $farmerId,
        ?string $farmName = null,
        ?float $farmSize = null,
        ?array $crops = null,
        ?string $region = null,
        ?string $village = null,
        ?string $phone = null,
        ?string $address = null,
        ?string $cooperativeName = null,
        ?string $cooperativeId = null,
    ): Farmer {
        $farmer = $this->farmerRepository->findById($farmerId);

        if ($farmer === null) {
            throw new FarmerNotFoundException("Farmer not found: {$farmerId}");
        }

        $farmer->updateProfile(
            farmName: $farmName,
            farmSize: $farmSize !== null ? FarmSize::fromHectares($farmSize) : null,
            crops: $crops !== null ? CropTypes::fromArray($crops) : null,
            region: $region !== null ? Region::fromString($region) : null,
            village: $village,
            phone: $phone,
            address: $address,
            cooperativeName: $cooperativeName,
            cooperativeId: $cooperativeId,
        );

        $this->farmerRepository->save($farmer);

        return $farmer;
    }
}
