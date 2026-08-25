<?php

namespace App\Receipt\Infrastructure\Persistence;

use App\Receipt\Domain\Entities\WarehouseReceipt;
use App\Receipt\Domain\Repositories\WarehouseReceiptRepositoryInterface;
use App\Receipt\Domain\ValueObjects\ReceiptStatus;
use App\Models\WarehouseReceipt as EloquentWarehouseReceipt;

class EloquentWarehouseReceiptRepository implements WarehouseReceiptRepositoryInterface
{
    public function save(WarehouseReceipt $receipt): void
    {
        $eloquentReceipt = EloquentWarehouseReceipt::query()
            ->where('id', $receipt->getId())
            ->first();

        if ($eloquentReceipt === null) {
            $eloquentReceipt = new EloquentWarehouseReceipt();
            $eloquentReceipt->id = $receipt->getId();
        }

        $eloquentReceipt->receipt_number = $receipt->getReceiptNumber();
        $eloquentReceipt->warehouse_id = $receipt->getWarehouseId();
        $eloquentReceipt->stock_id = $receipt->getStockId();
        $eloquentReceipt->farmer_id = $receipt->getFarmerId();
        $eloquentReceipt->crop_type = $receipt->getCropType();
        $eloquentReceipt->quantity_kg = $receipt->getQuantityKg();
        $eloquentReceipt->issue_date = $receipt->getIssueDate()->format('Y-m-d');
        $eloquentReceipt->qr_code_data = $receipt->getQrCodeData();
        $eloquentReceipt->integrity_hash = $receipt->getIntegrityHash();
        $eloquentReceipt->status = $receipt->getStatus()->toString();
        $eloquentReceipt->created_at = $receipt->getCreatedAt()->format('Y-m-d H:i:s');
        $eloquentReceipt->updated_at = $receipt->getUpdatedAt()?->format('Y-m-d H:i:s');

        $eloquentReceipt->save();
    }

    public function findById(string $id): ?WarehouseReceipt
    {
        $eloquentReceipt = EloquentWarehouseReceipt::find($id);

        if ($eloquentReceipt === null) {
            return null;
        }

        return $this->toDomain($eloquentReceipt);
    }

    public function findByReceiptNumber(string $receiptNumber): ?WarehouseReceipt
    {
        $eloquentReceipt = EloquentWarehouseReceipt::where('receipt_number', $receiptNumber)->first();

        if ($eloquentReceipt === null) {
            return null;
        }

        return $this->toDomain($eloquentReceipt);
    }

    public function findByFarmerId(string $farmerId): array
    {
        $eloquentReceipts = EloquentWarehouseReceipt::where('farmer_id', $farmerId)->get();

        return $eloquentReceipts->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function findByWarehouseId(string $warehouseId): array
    {
        $eloquentReceipts = EloquentWarehouseReceipt::where('warehouse_id', $warehouseId)->get();

        return $eloquentReceipts->map(fn ($eloquent) => $this->toDomain($eloquent))->toArray();
    }

    public function delete(WarehouseReceipt $receipt): void
    {
        EloquentWarehouseReceipt::where('id', $receipt->getId())->delete();
    }

    private function toDomain(EloquentWarehouseReceipt $eloquent): WarehouseReceipt
    {
        $receipt = WarehouseReceipt::issue(
            id: $eloquent->id,
            receiptNumber: $eloquent->receipt_number,
            warehouseId: $eloquent->warehouse_id,
            stockId: $eloquent->stock_id,
            farmerId: $eloquent->farmer_id,
            cropType: $eloquent->crop_type,
            quantityKg: (float) $eloquent->quantity_kg,
            qrCodeData: $eloquent->qr_code_data,
        );

        if ($eloquent->status === ReceiptStatus::REDEEMED->value) {
            $receipt->redeem();
        }

        if ($eloquent->status === ReceiptStatus::CANCELLED->value) {
            $receipt->cancel();
        }

        $receipt->hydrateIntegrityHash($eloquent->integrity_hash);

        return $receipt;
    }
}
