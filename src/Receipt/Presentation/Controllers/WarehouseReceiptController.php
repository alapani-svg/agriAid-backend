<?php

namespace App\Receipt\Presentation\Controllers;

use App\Receipt\Domain\Entities\WarehouseReceipt;
use App\Receipt\Domain\Repositories\WarehouseReceiptRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseReceiptController
{
    public function __construct(
        private readonly WarehouseReceiptRepositoryInterface $receiptRepository
    ) {}

    public function show(string $id): JsonResponse
    {
        $receipt = $this->receiptRepository->findById($id);

        if ($receipt === null) {
            return response()->json(['error' => 'Receipt not found'], 404);
        }

        return response()->json($this->toArray($receipt));
    }

    public function index(Request $request): JsonResponse
    {
        $farmerId = $request->query('farmer_id');
        $warehouseId = $request->query('warehouse_id');

        if ($farmerId) {
            $receipts = $this->receiptRepository->findByFarmerId($farmerId);
        } elseif ($warehouseId) {
            $receipts = $this->receiptRepository->findByWarehouseId($warehouseId);
        } else {
            $receipts = [];
        }

        return response()->json([
            'data' => array_map(fn ($receipt) => $this->toArray($receipt), $receipts),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(WarehouseReceipt $receipt): array
    {
        return [
            'id' => $receipt->getId(),
            'receipt_number' => $receipt->getReceiptNumber(),
            'warehouse_id' => $receipt->getWarehouseId(),
            'stock_id' => $receipt->getStockId(),
            'farmer_id' => $receipt->getFarmerId(),
            'crop_type' => $receipt->getCropType(),
            'quantity_kg' => $receipt->getQuantityKg(),
            'issue_date' => $receipt->getIssueDate()->format('Y-m-d'),
            'qr_code_svg' => $receipt->getQrCodeData(),
            'integrity_hash' => $receipt->getIntegrityHash(),
            'status' => $receipt->getStatus()->toString(),
            'created_at' => $receipt->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $receipt->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
