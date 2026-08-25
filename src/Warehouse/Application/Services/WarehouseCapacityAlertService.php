<?php

namespace App\Warehouse\Application\Services;

use App\Models\User;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;

/**
 * Evaluates a warehouse's overall stock utilization after a stock-changing
 * event (e.g. a harvest being stored) and alerts the warehouse manager via
 * the notification center once configured thresholds are crossed.
 */
class WarehouseCapacityAlertService
{
    /** Utilization percentage at/above which a "critical" alert is sent. */
    private const CRITICAL_THRESHOLD_PCT = 90.0;

    /** Utilization percentage at/above which a "warning" alert is sent. */
    private const WARNING_THRESHOLD_PCT = 75.0;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly StockRepositoryInterface $stockRepository,
        private readonly NotificationApplicationService $notificationService,
    ) {}

    public function checkAndNotify(string $warehouseId): void
    {
        $warehouse = $this->warehouseRepository->findById($warehouseId);

        if ($warehouse === null || $warehouse->getManagerUserId() === null) {
            return;
        }

        $capacityUsedKg = 0.0;
        foreach ($this->stockRepository->findByWarehouseId($warehouseId) as $stock) {
            if ($stock->isInStock()) {
                $capacityUsedKg += $stock->getQuantityKg();
            }
        }

        $totalKg = $warehouse->getCapacityTotalKg();
        if ($totalKg <= 0) {
            return;
        }

        $utilizationPct = ($capacityUsedKg / $totalKg) * 100;

        $level = null;
        if ($utilizationPct >= self::CRITICAL_THRESHOLD_PCT) {
            $level = 'critical';
        } elseif ($utilizationPct >= self::WARNING_THRESHOLD_PCT) {
            $level = 'warning';
        }

        if ($level === null) {
            return;
        }

        $manager = User::find($warehouse->getManagerUserId());
        if ($manager === null) {
            return;
        }

        $type = $level === 'critical'
            ? NotificationType::WAREHOUSE_CAPACITY_CRITICAL
            : NotificationType::WAREHOUSE_CAPACITY_WARNING;

        $title = $level === 'critical'
            ? 'Warehouse nearly full'
            : 'Warehouse approaching capacity';

        $message = sprintf(
            '%s is at %.0f%% of its %s kg capacity (%s kg stored).',
            $warehouse->getName(),
            $utilizationPct,
            number_format($totalKg, 0),
            number_format($capacityUsedKg, 0),
        );

        // One alert per level per warehouse per day, so a burst of harvests
        // doesn't spam the manager with duplicate notifications.
        $idempotencyKey = sprintf(
            'warehouse.capacity.%s:%s:%s',
            $level,
            $warehouseId,
            now()->format('Y-m-d'),
        );

        $this->notificationService->notify(
            user: $manager,
            type: $type,
            title: $title,
            message: $message,
            deepLink: '/dashboard/warehouse',
            idempotencyKey: $idempotencyKey,
        );
    }
}
