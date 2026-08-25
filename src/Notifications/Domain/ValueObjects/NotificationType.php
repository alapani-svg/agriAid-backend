<?php

namespace App\Notifications\Domain\ValueObjects;

enum NotificationType: string
{
    case WELCOME = 'account.welcome';
    case FARMER_PROFILE_REGISTERED = 'farmer.profile.registered';
    case HARVEST_RECORDED = 'harvest.recorded';
    case HARVEST_SENT_TO_WAREHOUSE = 'harvest.sent_to_warehouse';
    case HARVEST_STORED = 'harvest.stored';
    case WAREHOUSE_CAPACITY_WARNING = 'warehouse.capacity.warning';
    case WAREHOUSE_CAPACITY_CRITICAL = 'warehouse.capacity.critical';
    case SYSTEM = 'system.alert';

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $type): self
    {
        return self::tryFrom($type) ?? self::SYSTEM;
    }
}
