<?php

namespace App\Receipt\Domain\Entities;

use App\Shared\Domain\AggregateRoot;
use App\Receipt\Domain\Events\WarehouseReceiptIssued;
use App\Receipt\Domain\ValueObjects\ReceiptStatus;

final class WarehouseReceipt extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private readonly string $receiptNumber,
        private readonly string $warehouseId,
        private ?string $stockId,
        private readonly string $farmerId,
        private readonly string $cropType,
        private readonly float $quantityKg,
        private readonly \DateTimeImmutable $issueDate,
        private ?string $qrCodeData,
        private ReceiptStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
        private ?string $integrityHash = null,
    ) {}

    public static function issue(
        string $id,
        string $receiptNumber,
        string $warehouseId,
        ?string $stockId,
        string $farmerId,
        string $cropType,
        float $quantityKg,
        ?string $qrCodeData = null,
    ): self {
        if ($quantityKg <= 0) {
            throw new \InvalidArgumentException('Receipt quantity must be positive');
        }

        $issueDate = new \DateTimeImmutable();

        $receipt = new self(
            id: $id,
            receiptNumber: $receiptNumber,
            warehouseId: $warehouseId,
            stockId: $stockId,
            farmerId: $farmerId,
            cropType: $cropType,
            quantityKg: $quantityKg,
            issueDate: $issueDate,
            qrCodeData: $qrCodeData,
            status: ReceiptStatus::ACTIVE,
            createdAt: new \DateTimeImmutable(),
        );

        $receipt->integrityHash = self::computeIntegrityHash(
            $id,
            $receiptNumber,
            $warehouseId,
            $farmerId,
            $cropType,
            $quantityKg,
            $issueDate,
        );

        $receipt->recordEvent(new WarehouseReceiptIssued($receipt));

        return $receipt;
    }

    /**
     * A real, deterministic SHA-256 hash of the receipt's canonical fields.
     * This is a genuine cryptographic integrity seal used to detect tampering —
     * it is NOT a blockchain, and does not claim distributed-ledger properties.
     */
    public static function computeIntegrityHash(
        string $id,
        string $receiptNumber,
        string $warehouseId,
        string $farmerId,
        string $cropType,
        float $quantityKg,
        \DateTimeImmutable $issueDate,
    ): string {
        $canonical = implode('|', [
            $id,
            $receiptNumber,
            $warehouseId,
            $farmerId,
            $cropType,
            number_format($quantityKg, 2, '.', ''),
            $issueDate->format('Y-m-d'),
        ]);

        return hash('sha256', $canonical);
    }

    public function redeem(): void
    {
        if (!$this->status->canBeRedeemed()) {
            throw new \DomainException('Receipt cannot be redeemed in its current status');
        }

        $this->status = ReceiptStatus::REDEEMED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (!$this->status->canBeCancelled()) {
            throw new \DomainException('Receipt cannot be cancelled in its current status');
        }

        $this->status = ReceiptStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function attachStock(string $stockId): void
    {
        $this->stockId = $stockId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getReceiptNumber(): string
    {
        return $this->receiptNumber;
    }

    public function getWarehouseId(): string
    {
        return $this->warehouseId;
    }

    public function getStockId(): ?string
    {
        return $this->stockId;
    }

    public function getFarmerId(): string
    {
        return $this->farmerId;
    }

    public function getCropType(): string
    {
        return $this->cropType;
    }

    public function getQuantityKg(): float
    {
        return $this->quantityKg;
    }

    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function getQrCodeData(): ?string
    {
        return $this->qrCodeData;
    }

    public function getStatus(): ReceiptStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getIntegrityHash(): ?string
    {
        return $this->integrityHash;
    }

    /**
     * Restores the originally-computed integrity hash when rehydrating from
     * persistence (the hash is bound to the original issue date/time, so it
     * must be loaded verbatim rather than recomputed).
     */
    public function hydrateIntegrityHash(?string $integrityHash): void
    {
        $this->integrityHash = $integrityHash;
    }
}
