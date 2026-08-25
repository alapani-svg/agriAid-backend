<?php

namespace App\Receipt\Application\Services;

use App\Receipt\Domain\Entities\WarehouseReceipt;
use App\Receipt\Domain\Repositories\WarehouseReceiptRepositoryInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

class IssueWarehouseReceiptService
{
    public function __construct(
        private readonly WarehouseReceiptRepositoryInterface $receiptRepository
    ) {}

    public function execute(
        string $warehouseId,
        string $farmerId,
        string $cropType,
        float $quantityKg,
        ?string $stockId = null,
    ): WarehouseReceipt {
        $receiptNumber = $this->generateReceiptNumber();

        $qrCodeData = $this->generateQrCode($receiptNumber, $warehouseId, $farmerId, $cropType, $quantityKg);

        $receipt = WarehouseReceipt::issue(
            id: (string) Str::uuid(),
            receiptNumber: $receiptNumber,
            warehouseId: $warehouseId,
            stockId: $stockId,
            farmerId: $farmerId,
            cropType: $cropType,
            quantityKg: $quantityKg,
            qrCodeData: $qrCodeData,
        );

        $this->receiptRepository->save($receipt);

        return $receipt;
    }

    private function generateReceiptNumber(): string
    {
        return sprintf(
            'WR-%s-%s',
            now()->format('Ymd'),
            strtoupper(Str::random(6))
        );
    }

    private function generateQrCode(
        string $receiptNumber,
        string $warehouseId,
        string $farmerId,
        string $cropType,
        float $quantityKg,
    ): string {
        $payload = json_encode([
            'receipt_number' => $receiptNumber,
            'warehouse_id' => $warehouseId,
            'farmer_id' => $farmerId,
            'crop_type' => $cropType,
            'quantity_kg' => $quantityKg,
        ]);

        $qrCode = new QrCode(data: $payload);
        $writer = new SvgWriter();

        return $writer->write($qrCode)->getString();
    }
}
