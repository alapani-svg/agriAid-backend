<?php

namespace App\Stock\Presentation\Controllers;

use App\Stock\Application\Services\StockPhotoVerificationService;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Domain\ValueObjects\StockStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StockController
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly StockPhotoVerificationService $photoVerificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $warehouseId = $request->query('warehouse_id');
        $status = $request->query('status');
        $harvestId = $request->query('harvest_id');
        $farmerId = $request->query('farmer_id');
        $all = $request->query('all');

        if ($all && $request->user()?->role === 'admin') {
            $stocks = $this->stockRepository->findAll();
        } elseif ($farmerId) {
            $stocks = $this->stockRepository->findByFarmerId($farmerId);
        } elseif ($harvestId) {
            $stock = $this->stockRepository->findByHarvestId($harvestId);
            $stocks = $stock ? [$stock] : [];
        } elseif ($warehouseId) {
            $stocks = $this->stockRepository->findByWarehouseId($warehouseId);
        } elseif ($status) {
            try {
                $stocks = $this->stockRepository->findByStatus(StockStatus::fromString($status));
            } catch (\ValueError $e) {
                return response()->json(['error' => "Invalid status: {$status}"], 422);
            }
        } else {
            $stocks = [];
        }

        return response()->json([
            'data' => array_map(fn ($stock) => $this->toArray($stock), $stocks),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $stock = $this->stockRepository->findById($id);

        if ($stock === null) {
            return response()->json(['error' => 'Stock not found'], 404);
        }

        return response()->json($this->toArray($stock));
    }

    public function verifyPhoto(Request $request, string $id): JsonResponse
    {
        $stock = $this->stockRepository->findById($id);

        if ($stock === null) {
            return response()->json(['error' => 'Stock not found'], 404);
        }

        if (!$request->hasFile('photo') || !$request->file('photo')->isValid()) {
            return response()->json(['errors' => ['photo' => 'A valid photo is required.']], 422);
        }

        $photoPath = $request->file('photo')->store('goods-photos', 'public');

        $analysis = $this->photoVerificationService->analyze(
            $photoPath,
            $stock->getCropType()->toString(),
            $stock->getQuantityKg(),
        );

        $stock->attachPhotoVerification(
            photoPath: $photoPath,
            aiEstimatedQuantityKg: $analysis['ai_estimated_quantity_kg'],
            aiAnalysisNotes: $analysis['ai_analysis_notes'],
            verificationStatus: $analysis['verification_status'],
        );

        $this->stockRepository->save($stock);

        return response()->json($this->toArray($stock), 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(\App\Stock\Domain\Entities\Stock $stock): array
    {
        // Load the Eloquent model to access validation fields (not part of the domain entity)
        $eloquent = \App\Models\Stock::find($stock->getId());

        return [
            'id' => $stock->getId(),
            'warehouse_id' => $stock->getWarehouseId(),
            'harvest_id' => $stock->getHarvestId(),
            'crop_type' => $stock->getCropType()->toString(),
            'quantity_kg' => $stock->getQuantityKg(),
            'capacity_used' => $stock->getCapacity()->getUsed(),
            'capacity_total' => $stock->getCapacity()->getTotal(),
            'capacity_available' => $stock->getCapacity()->getAvailable(),
            'utilization_percentage' => $stock->getCapacity()->getUtilizationPercentage(),
            'entry_date' => $stock->getEntryDate()->format('Y-m-d'),
            'exit_date' => $stock->getExitDate()?->format('Y-m-d'),
            'status' => $stock->getStatus()->toString(),
            'notes' => $stock->getNotes(),
            'photo_path' => $stock->getPhotoPath(),
            'photo_url' => $stock->getPhotoPath() ? Storage::disk('public')->url($stock->getPhotoPath()) : null,
            'ai_estimated_quantity_kg' => $stock->getAiEstimatedQuantityKg(),
            'ai_analysis_notes' => $stock->getAiAnalysisNotes(),
            'verification_status' => $stock->getVerificationStatus(),
            'validation_status' => $eloquent?->validation_status ?? 'pending',
            'validation_notes' => $eloquent?->validation_notes,
            'price_per_kg' => $eloquent?->price_per_kg !== null ? (float) $eloquent->price_per_kg : null,
            'currency' => $eloquent?->currency ?? 'FCFA',
            'seller_id' => $eloquent?->seller_id,
            'created_at' => $stock->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $stock->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
