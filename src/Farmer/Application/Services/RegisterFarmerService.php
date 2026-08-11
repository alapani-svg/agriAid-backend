<?php

namespace App\Farmer\Application\Services;

use App\Farmer\Domain\Entities\Farmer;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Domain\ValueObjects\CropTypes;
use App\Farmer\Domain\ValueObjects\FarmSize;
use App\Farmer\Domain\ValueObjects\Region;
use App\Farmer\Domain\Exceptions\FarmerAlreadyExistsException;
use Illuminate\Support\Str;

class RegisterFarmerService
{
    public function __construct(
        private readonly FarmerRepositoryInterface $farmerRepository
    ) {}

    public function execute(
        string $userId,
        string $farmName,
        float $farmSize,
        array $crops,
        string $region,
        string $village,
        ?string $phone = null,
        ?string $address = null,
        ?string $cooperativeName = null,
        ?string $cooperativeId = null,
    ): Farmer {
        if ($this->farmerRepository->existsByUserId($userId)) {
            throw new FarmerAlreadyExistsException("Farmer profile already exists for user: {$userId}");
        }

        $farmer = Farmer::register(
            id: (string) Str::uuid(),
            userId: $userId,
            farmName: $farmName,
            farmSize: FarmSize::fromHectares($farmSize),
            crops: CropTypes::fromArray($crops),
            region: Region::fromString($region),
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
