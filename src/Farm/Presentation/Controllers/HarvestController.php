<?php

namespace App\Farm\Presentation\Controllers;

use App\Farm\Application\Services\RecordHarvestService;
use App\Farm\Application\Services\SendHarvestToWarehouseService;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Presentation\DTOs\RecordHarvestRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HarvestController
{
    public function __construct(
        private readonly RecordHarvestService $recordHarvestService,
        private readonly SendHarvestToWarehouseService $sendHarvestToWarehouseService,
        private readonly HarvestRepositoryInterface $harvestRepository
    ) {}

    public function record(Request $request): JsonResponse
    {
        $dto = RecordHarvestRequest::fromArray($request->all());
        $errors = $dto->validate();

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        try {
            $harvest = $this->recordHarvestService->execute(
                farmerId: $dto->farmerId,
                cropType: $dto->cropType,
                quantityKg: $dto->quantityKg,
                harvestDate: $dto->harvestDate,
                qualityGrade: $dto->qualityGrade,
                notes: $dto->notes,
            );

            return response()->json([
                'id' => $harvest->getId(),
                'farmer_id' => $harvest->getFarmerId(),
                'warehouse_id' => $harvest->getWarehouseId(),
                'crop_type' => $harvest->getCropType()->toString(),
                'quantity_kg' => $harvest->getQuantity()->toKilograms(),
                'quality_grade' => $harvest->getQualityGrade()?->toScore(),
                'harvest_date' => $harvest->getHarvestDate()->format('Y-m-d'),
                'storage_date' => $harvest->getStorageDate()?->format('Y-m-d'),
                'status' => $harvest->getStatus()->toString(),
                'notes' => $harvest->getNotes(),
                'created_at' => $harvest->getCreatedAt()->format('Y-m-d H:i:s'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $harvest = $this->harvestRepository->findById($id);

        if ($harvest === null) {
            return response()->json(['error' => 'Harvest not found'], 404);
        }

        return response()->json([
            'id' => $harvest->getId(),
            'farmer_id' => $harvest->getFarmerId(),
            'warehouse_id' => $harvest->getWarehouseId(),
            'crop_type' => $harvest->getCropType()->toString(),
            'quantity_kg' => $harvest->getQuantity()->toKilograms(),
            'quality_grade' => $harvest->getQualityGrade()?->toScore(),
            'harvest_date' => $harvest->getHarvestDate()->format('Y-m-d'),
            'storage_date' => $harvest->getStorageDate()?->format('Y-m-d'),
            'status' => $harvest->getStatus()->toString(),
            'notes' => $harvest->getNotes(),
            'created_at' => $harvest->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $harvest->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $farmerId = $request->query('farmer_id');
        
        if ($farmerId) {
            $harvests = $this->harvestRepository->findByFarmerId($farmerId);
        } else {
            $harvests = [];
        }

        return response()->json([
            'data' => array_map(fn ($harvest) => [
                'id' => $harvest->getId(),
                'farmer_id' => $harvest->getFarmerId(),
                'warehouse_id' => $harvest->getWarehouseId(),
                'crop_type' => $harvest->getCropType()->toString(),
                'quantity_kg' => $harvest->getQuantity()->toKilograms(),
                'quality_grade' => $harvest->getQualityGrade()?->toScore(),
                'harvest_date' => $harvest->getHarvestDate()->format('Y-m-d'),
                'storage_date' => $harvest->getStorageDate()?->format('Y-m-d'),
                'status' => $harvest->getStatus()->toString(),
                'notes' => $harvest->getNotes(),
                'created_at' => $harvest->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $harvests),
        ]);
    }

    public function sendToWarehouse(Request $request, string $id): JsonResponse
    {
        $warehouseId = $request->input('warehouse_id');

        if (empty($warehouseId)) {
            return response()->json(['error' => 'Warehouse ID is required'], 422);
        }

        try {
            $harvest = $this->sendHarvestToWarehouseService->execute(
                harvestId: $id,
                warehouseId: $warehouseId,
            );

            return response()->json([
                'id' => $harvest->getId(),
                'farmer_id' => $harvest->getFarmerId(),
                'warehouse_id' => $harvest->getWarehouseId(),
                'crop_type' => $harvest->getCropType()->toString(),
                'quantity_kg' => $harvest->getQuantity()->toKilograms(),
                'status' => $harvest->getStatus()->toString(),
                'updated_at' => $harvest->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
