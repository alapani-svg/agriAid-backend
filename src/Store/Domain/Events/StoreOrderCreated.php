<?php

namespace App\Store\Domain\Events;

use App\Store\Domain\Entities\StoreOrder;

final readonly class StoreOrderCreated
{
    public function __construct(
        public StoreOrder $order
    ) {}
}
