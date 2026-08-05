<?php

namespace App\Farm\Infrastructure\Persistence;

use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Domain\ValueObjects\CropType;
use App\Farm\Domain\ValueObjects\HarvestStatus;
use App\Farm\Domain\ValueObjects\QualityGrade;
use App\Farm\Domain\ValueObjects\Quantity;
use App\Models\Harvest as EloquentHarvest;

class EloquentHarvestRepository implements HarvestRepositoryInterface
{
    public function save(Harvest $harvest): void
    {
        $eloquentHarvest = EloquentHarvest::query()
            ->where('id', $harvest->getId())
            ->first();

        if ($eloquentHarvest === null) {
            $eloquentHarvest = new EloquentHarvest();
            $eloquentHarvest->id = $harvest->getId();
        }

        $eloquentHarvest->farmer_id = $harvest->getFarmerId();
        $eloquentHarvest->warehouse_id = $harvest->getWarehouseId();
        $eloquentHarvest->crop_type = $harvest->getCropType()->toString();
        $eloquentHarvest->quantity_kg = $harvest->getQuantity()->toKilograms();
        $eloquentHarvest->quality_grade = $harvest->getQualityGrade()?->toScore();
        $eloquentHarvest->harvest_date = $harvest->getHarvestDate()->format('Y-m-d');
        $eloquentHarvest->storage_date = $harvest->getStorageDate()?->format('Y-m-d');
        $eloquentHarvest->status = $harvest->getStatus()->toString();
        $eloquentHarvest->notes = $harvest->getNotes();
        $eloquentHarvest->created_at = $harvest->getCreatedAt()->format('Y-m-d H:i:s');
        $eloquentHarvest->updated_at = $harvest->getUpdatedAt()?->format('Y-m-d H:i:s');

        $eloquentHarvest->save();
    }

    public function findById(string $id): ?Harvest
    {
        $eloquentHarvest = EloquentHarvest::find($id);

        if ($eloquentHarvest === null) {
            return null;
        }

        return $this->toDomain($eloquentHarvest);
    }

    public function findByFarmerId(string $farmerId): array
    {
        $eloquentHarvests = EloquentHarvest::where('farmer_id', $farmerId)->get();

        return $eloquentHarvests->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findByWarehouseId(string $warehouseId): array
    {
        $eloquentHarvests = EloquentHarvest::where('warehouse_id', $warehouseId)->get();

        return $eloquentHarvests->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findByStatus(HarvestStatus $status): array
    {
        $eloquentHarvests = EloquentHarvest::where('status', $status->toString())->get();

        return $eloquentHarvests->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function delete(Harvest $harvest): void
    {
        EloquentHarvest::where('id', $harvest->getId())->delete();
    }

    private function toDomain(EloquentHarvest $eloquent): Harvest
    {
        $harvest = Harvest::record(
            id: $eloquent->id,
            farmerId: $eloquent->farmer_id,
            cropType: CropType::fromString($eloquent->crop_type),
            quantity: Quantity::fromKilograms((float) $eloquent->quantity_kg),
            harvestDate: new \DateTimeImmutable($eloquent->harvest_date),
            qualityGrade: $eloquent->quality_grade ? QualityGrade::fromScore((float) $eloquent->quality_grade) : null,
            notes: $eloquent->notes,
        );

        // Restore state if not in initial status
        if ($eloquent->warehouse_id) {
            $harvest->sendToWarehouse($eloquent->warehouse_id);
        }

        if ($eloquent->status === HarvestStatus::STORED->value) {
            $harvest->storeInWarehouse();
        }

        if ($eloquent->status === HarvestStatus::SOLD->value) {
            $harvest->markAsSold();
        }

        return $harvest;
    }
}
