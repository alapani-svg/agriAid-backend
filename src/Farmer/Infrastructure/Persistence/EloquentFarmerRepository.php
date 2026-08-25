<?php

namespace App\Farmer\Infrastructure\Persistence;

use App\Farmer\Domain\Entities\Farmer;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Domain\ValueObjects\CropTypes;
use App\Farmer\Domain\ValueObjects\FarmSize;
use App\Farmer\Domain\ValueObjects\FarmerStatus;
use App\Farmer\Domain\ValueObjects\Region;
use App\Models\Farmer as EloquentFarmer;

class EloquentFarmerRepository implements FarmerRepositoryInterface
{
    public function save(Farmer $farmer): void
    {
        $eloquentFarmer = EloquentFarmer::query()
            ->where('id', $farmer->getId())
            ->first();

        if ($eloquentFarmer === null) {
            $eloquentFarmer = new EloquentFarmer();
            $eloquentFarmer->id = $farmer->getId();
        }

        $eloquentFarmer->user_id = $farmer->getUserId();
        $eloquentFarmer->farm_name = $farmer->getFarmName();
        $eloquentFarmer->farm_size = $farmer->getFarmSize()->toHectares();
        $eloquentFarmer->crops = $farmer->getCrops()->toArray();
        $eloquentFarmer->region = $farmer->getRegion()->toString();
        $eloquentFarmer->village = $farmer->getVillage();
        $eloquentFarmer->phone = $farmer->getPhone();
        $eloquentFarmer->address = $farmer->getAddress();
        $eloquentFarmer->cooperative_name = $farmer->getCooperativeName();
        $eloquentFarmer->cooperative_id = $farmer->getCooperativeId();
        $eloquentFarmer->status = $farmer->getStatus()->toString();
        $eloquentFarmer->created_at = $farmer->getCreatedAt()->format('Y-m-d H:i:s');
        $eloquentFarmer->updated_at = $farmer->getUpdatedAt()?->format('Y-m-d H:i:s');

        $eloquentFarmer->save();
    }

    public function findById(string $id): ?Farmer
    {
        $eloquentFarmer = EloquentFarmer::find($id);

        if ($eloquentFarmer === null) {
            return null;
        }

        return $this->toDomain($eloquentFarmer);
    }

    public function findByUserId(string $userId): ?Farmer
    {
        $eloquentFarmer = EloquentFarmer::where('user_id', $userId)->first();

        if ($eloquentFarmer === null) {
            return null;
        }

        return $this->toDomain($eloquentFarmer);
    }

    public function findByRegion(Region $region): array
    {
        $eloquentFarmers = EloquentFarmer::where('region', $region->toString())->get();

        return $eloquentFarmers->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findAllActive(): array
    {
        $eloquentFarmers = EloquentFarmer::where('status', FarmerStatus::ACTIVE->value)->get();

        return $eloquentFarmers->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findAll(): array
    {
        $eloquentFarmers = EloquentFarmer::orderByDesc('created_at')->get();

        return $eloquentFarmers->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function delete(Farmer $farmer): void
    {
        EloquentFarmer::where('id', $farmer->getId())->delete();
    }

    public function existsByUserId(string $userId): bool
    {
        return EloquentFarmer::where('user_id', $userId)->exists();
    }

    private function toDomain(EloquentFarmer $eloquent): Farmer
    {
        return Farmer::register(
            id: $eloquent->id,
            userId: $eloquent->user_id,
            farmName: $eloquent->farm_name,
            farmSize: FarmSize::fromHectares((float) $eloquent->farm_size),
            crops: CropTypes::fromArray((array) $eloquent->crops),
            region: Region::fromString($eloquent->region),
            village: $eloquent->village,
            phone: $eloquent->phone,
            address: $eloquent->address,
            cooperativeName: $eloquent->cooperative_name,
            cooperativeId: $eloquent->cooperative_id,
        );
    }
}
