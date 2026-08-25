<?php

namespace App\Warehouse\Infrastructure\Persistence;

use App\Warehouse\Domain\Entities\Warehouse;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use App\Warehouse\Domain\ValueObjects\WarehouseStatus;
use App\Models\Warehouse as EloquentWarehouse;

class EloquentWarehouseRepository implements WarehouseRepositoryInterface
{
    public function save(Warehouse $warehouse): void
    {
        $eloquentWarehouse = EloquentWarehouse::query()
            ->where('id', $warehouse->getId())
            ->first();

        if ($eloquentWarehouse === null) {
            $eloquentWarehouse = new EloquentWarehouse();
            $eloquentWarehouse->id = $warehouse->getId();
        }

        $eloquentWarehouse->manager_user_id = $warehouse->getManagerUserId();
        $eloquentWarehouse->farmer_id = $warehouse->getFarmerId();
        $eloquentWarehouse->name = $warehouse->getName();
        $eloquentWarehouse->region = $warehouse->getRegion();
        $eloquentWarehouse->village = $warehouse->getVillage();
        $eloquentWarehouse->address = $warehouse->getAddress();
        $eloquentWarehouse->capacity_total_kg = $warehouse->getCapacityTotalKg();
        $eloquentWarehouse->status = $warehouse->getStatus()->toString();
        $eloquentWarehouse->notes = $warehouse->getNotes();
        $eloquentWarehouse->aeration_active = $warehouse->isAerationActive();
        $eloquentWarehouse->aeration_updated_at = $warehouse->getAerationUpdatedAt()?->format('Y-m-d H:i:s');
        $eloquentWarehouse->created_at = $warehouse->getCreatedAt()->format('Y-m-d H:i:s');
        $eloquentWarehouse->updated_at = $warehouse->getUpdatedAt()?->format('Y-m-d H:i:s');

        $eloquentWarehouse->save();
    }

    public function findById(string $id): ?Warehouse
    {
        $eloquentWarehouse = EloquentWarehouse::find($id);

        if ($eloquentWarehouse === null) {
            return null;
        }

        return $this->toDomain($eloquentWarehouse);
    }

    public function findByRegion(string $region): array
    {
        $eloquentWarehouses = EloquentWarehouse::where('region', $region)->get();

        return $eloquentWarehouses->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findAllActive(): array
    {
        $eloquentWarehouses = EloquentWarehouse::where('status', WarehouseStatus::ACTIVE->value)->get();

        return $eloquentWarehouses->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findAll(): array
    {
        $eloquentWarehouses = EloquentWarehouse::all();

        return $eloquentWarehouses->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findByManagerUserId(string $managerUserId): array
    {
        $eloquentWarehouses = EloquentWarehouse::where('manager_user_id', $managerUserId)->get();

        return $eloquentWarehouses->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findByFarmerId(string $farmerId): array
    {
        $eloquentWarehouses = EloquentWarehouse::where('farmer_id', $farmerId)->get();

        return $eloquentWarehouses->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function delete(Warehouse $warehouse): void
    {
        EloquentWarehouse::where('id', $warehouse->getId())->delete();
    }

    private function toDomain(EloquentWarehouse $eloquent): Warehouse
    {
        $warehouse = Warehouse::register(
            id: $eloquent->id,
            name: $eloquent->name,
            region: $eloquent->region,
            capacityTotalKg: (float) $eloquent->capacity_total_kg,
            managerUserId: $eloquent->manager_user_id,
            farmerId: $eloquent->farmer_id,
            village: $eloquent->village,
            address: $eloquent->address,
            notes: $eloquent->notes,
        );

        if ($eloquent->status === WarehouseStatus::INACTIVE->value) {
            $warehouse->deactivate();
        }

        $warehouse->hydrateAerationState(
            (bool) $eloquent->aeration_active,
            $eloquent->aeration_updated_at ? new \DateTimeImmutable((string) $eloquent->aeration_updated_at) : null,
        );

        return $warehouse;
    }
}
