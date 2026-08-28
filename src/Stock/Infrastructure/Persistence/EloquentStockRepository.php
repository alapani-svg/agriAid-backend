<?php

namespace App\Stock\Infrastructure\Persistence;

use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Domain\ValueObjects\Capacity;
use App\Stock\Domain\ValueObjects\StockStatus;
use App\Farm\Domain\ValueObjects\CropType;
use App\Models\Stock as EloquentStock;

class EloquentStockRepository implements StockRepositoryInterface
{
    public function save(Stock $stock): void
    {
        $eloquentStock = EloquentStock::query()
            ->where('id', $stock->getId())
            ->first();

        if ($eloquentStock === null) {
            $eloquentStock = new EloquentStock();
            $eloquentStock->id = $stock->getId();
        }

        $eloquentStock->warehouse_id = $stock->getWarehouseId();
        $eloquentStock->harvest_id = $stock->getHarvestId();
        $eloquentStock->crop_type = $stock->getCropType()->toString();
        $eloquentStock->quantity_kg = $stock->getQuantityKg();
        $eloquentStock->capacity_used = $stock->getCapacity()->getUsed();
        $eloquentStock->capacity_total = $stock->getCapacity()->getTotal();
        $eloquentStock->entry_date = $stock->getEntryDate()->format('Y-m-d');
        $eloquentStock->exit_date = $stock->getExitDate()?->format('Y-m-d');
        $eloquentStock->status = $stock->getStatus()->toString();
        $eloquentStock->notes = $stock->getNotes();
        $eloquentStock->photo_path = $stock->getPhotoPath();
        $eloquentStock->ai_estimated_quantity_kg = $stock->getAiEstimatedQuantityKg();
        $eloquentStock->ai_analysis_notes = $stock->getAiAnalysisNotes();
        $eloquentStock->verification_status = $stock->getVerificationStatus();
        $eloquentStock->created_at = $stock->getCreatedAt()->format('Y-m-d H:i:s');
        $eloquentStock->updated_at = $stock->getUpdatedAt()?->format('Y-m-d H:i:s');

        $eloquentStock->save();
    }

    public function findById(string $id): ?Stock
    {
        $eloquentStock = EloquentStock::find($id);

        if ($eloquentStock === null) {
            return null;
        }

        return $this->toDomain($eloquentStock);
    }

    public function findByWarehouseId(string $warehouseId): array
    {
        $eloquentStocks = EloquentStock::where('warehouse_id', $warehouseId)->get();

        return $eloquentStocks->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findByHarvestId(string $harvestId): ?Stock
    {
        $eloquentStock = EloquentStock::where('harvest_id', $harvestId)->first();

        if ($eloquentStock === null) {
            return null;
        }

        return $this->toDomain($eloquentStock);
    }

    public function findByFarmerId(string $farmerId): array
    {
        $eloquentStocks = EloquentStock::whereHas('harvest', function ($q) use ($farmerId) {
            $q->where('farmer_id', $farmerId);
        })->orderByDesc('created_at')->get();

        return $eloquentStocks->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findByStatus(StockStatus $status): array
    {
        $eloquentStocks = EloquentStock::where('status', $status->toString())->get();

        return $eloquentStocks->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findAll(): array
    {
        $eloquentStocks = EloquentStock::orderByDesc('created_at')->get();

        return $eloquentStocks->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function delete(Stock $stock): void
    {
        EloquentStock::where('id', $stock->getId())->delete();
    }

    private function toDomain(EloquentStock $eloquent): Stock
    {
        $stock = Stock::create(
            id: $eloquent->id,
            warehouseId: $eloquent->warehouse_id,
            harvestId: $eloquent->harvest_id,
            cropType: CropType::fromString($eloquent->crop_type),
            quantityKg: (float) $eloquent->quantity_kg,
            capacity: Capacity::fromValues(
                (float) $eloquent->capacity_used,
                (float) $eloquent->capacity_total
            ),
            entryDate: new \DateTimeImmutable($eloquent->entry_date),
            notes: $eloquent->notes,
        );

        // Restore state if not in initial status
        if ($eloquent->status === StockStatus::RESERVED->value) {
            $stock->reserve();
        }

        if ($eloquent->status === StockStatus::WITHDRAWN->value) {
            $stock->withdraw();
        }

        if ($eloquent->status === StockStatus::SOLD->value) {
            $stock->sell();
        }

        if ($eloquent->photo_path !== null || $eloquent->verification_status !== 'unavailable') {
            $stock->attachPhotoVerification(
                photoPath: $eloquent->photo_path,
                aiEstimatedQuantityKg: $eloquent->ai_estimated_quantity_kg !== null ? (float) $eloquent->ai_estimated_quantity_kg : null,
                aiAnalysisNotes: $eloquent->ai_analysis_notes,
                verificationStatus: $eloquent->verification_status,
            );
        }

        return $stock;
    }
}
