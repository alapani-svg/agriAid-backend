<?php

namespace App\Farm\Presentation\Controllers;

use App\Farm\Application\Services\RecordHarvestService;
use App\Farm\Application\Services\SendHarvestToWarehouseService;
use App\Farm\Application\Services\StoreHarvestInWarehouseService;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Presentation\DTOs\RecordHarvestRequest;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Models\User;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Warehouse\Application\Services\WarehouseCapacityAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HarvestController
{
    public function __construct(
        private readonly RecordHarvestService $recordHarvestService,
        private readonly SendHarvestToWarehouseService $sendHarvestToWarehouseService,
        private readonly StoreHarvestInWarehouseService $storeHarvestInWarehouseService,
        private readonly HarvestRepositoryInterface $harvestRepository,
        private readonly FarmerRepositoryInterface $farmerRepository,
        private readonly NotificationApplicationService $notificationService,
        private readonly WarehouseCapacityAlertService $warehouseCapacityAlertService,
    ) {}

    public function record(Request $request): JsonResponse
    {
        $dto = RecordHarvestRequest::fromArray($request->all());
        $errors = $dto->validate();

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        // Photo is required — a harvest cannot be recorded without visual proof of the crop
        if (!$request->hasFile('photo')) {
            return response()->json(['errors' => ['photo' => 'A photo of the harvested crop is required.']], 422);
        }

        if (!$request->file('photo')->isValid()) {
            return response()->json(['errors' => ['photo' => 'The uploaded photo is invalid.']], 422);
        }

        $photoPath = $request->file('photo')->store('harvest-photos', 'public');

        try {
            $harvest = $this->recordHarvestService->execute(
                farmerId: $dto->farmerId,
                cropType: $dto->cropType,
                quantityKg: $dto->quantityKg,
                harvestDate: $dto->harvestDate,
                qualityGrade: $dto->qualityGrade,
                notes: $dto->notes,
                photoPath: $photoPath,
            );

            if ($request->user() !== null) {
                $message = sprintf(
                    '%.1f kg of %s was recorded and added to your stock.',
                    $harvest->getQuantity()->toKilograms(),
                    $harvest->getCropType()->toString(),
                );

                if ($harvest->getVerificationStatus() === 'flagged') {
                    $message .= ' Our AI photo check flagged a mismatch with the declared quantity — this harvest may need review.';
                }

                $this->notificationService->notify(
                    user: $request->user(),
                    type: NotificationType::HARVEST_RECORDED,
                    title: 'Harvest recorded',
                    message: $message,
                    deepLink: '/dashboard/farmer',
                    idempotencyKey: "harvest.recorded:{$harvest->getId()}",
                );
            }

            return response()->json($this->toArray($harvest), 201);
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

        return response()->json($this->toArray($harvest));
    }

    public function index(Request $request): JsonResponse
    {
        $farmerId = $request->query('farmer_id');
        $warehouseId = $request->query('warehouse_id');
        $all = $request->query('all');

        if ($all && $request->user()?->role === 'admin') {
            $harvests = $this->harvestRepository->findAll();
        } elseif ($farmerId) {
            $harvests = $this->harvestRepository->findByFarmerId($farmerId);
        } elseif ($warehouseId) {
            $harvests = $this->harvestRepository->findByWarehouseId($warehouseId);
        } else {
            $harvests = [];
        }

        return response()->json([
            'data' => array_map(fn ($harvest) => $this->toArray($harvest), $harvests),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray($harvest): array
    {
        $photoPath = $harvest->getPhotoPath();

        return [
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
            'photo_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
            'ai_estimated_quantity_kg' => $harvest->getAiEstimatedQuantityKg(),
            'ai_analysis_notes' => $harvest->getAiAnalysisNotes(),
            'verification_status' => $harvest->getVerificationStatus(),
            'created_at' => $harvest->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $harvest->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
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

    public function storeInWarehouse(Request $request, string $id): JsonResponse
    {
        $warehouseId = $request->input('warehouse_id');

        if (empty($warehouseId)) {
            return response()->json(['error' => 'Warehouse ID is required'], 422);
        }

        try {
            $result = $this->storeHarvestInWarehouseService->execute(
                harvestId: $id,
                warehouseId: $warehouseId,
            );

            $harvest = $result['harvest'];
            $receipt = $result['receipt'];

            $farmer = $this->farmerRepository->findById($harvest->getFarmerId());
            if ($farmer !== null) {
                $farmerUser = User::find($farmer->getUserId());
                if ($farmerUser !== null) {
                    $this->notificationService->notify(
                        user: $farmerUser,
                        type: NotificationType::HARVEST_STORED,
                        title: 'Harvest stored in warehouse',
                        message: sprintf(
                            '%.1f kg of %s was stored and receipt %s was issued.',
                            $receipt->getQuantityKg(),
                            $harvest->getCropType()->toString(),
                            $receipt->getReceiptNumber(),
                        ),
                        deepLink: '/dashboard/farmer',
                        idempotencyKey: "harvest.stored:{$harvest->getId()}",
                    );
                }
            }

            $this->warehouseCapacityAlertService->checkAndNotify($warehouseId);

            return response()->json([
                'harvest' => [
                    'id' => $harvest->getId(),
                    'warehouse_id' => $harvest->getWarehouseId(),
                    'status' => $harvest->getStatus()->toString(),
                    'storage_date' => $harvest->getStorageDate()?->format('Y-m-d'),
                ],
                'receipt' => [
                    'id' => $receipt->getId(),
                    'receipt_number' => $receipt->getReceiptNumber(),
                    'stock_id' => $receipt->getStockId(),
                    'quantity_kg' => $receipt->getQuantityKg(),
                    'qr_code_svg' => $receipt->getQrCodeData(),
                    'integrity_hash' => $receipt->getIntegrityHash(),
                    'status' => $receipt->getStatus()->toString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
