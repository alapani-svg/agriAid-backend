<?php

namespace App\Store\Application\Services;

use App\Models\Stock as EloquentStock;
use App\Models\User;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Store\Domain\Entities\StoreOrder;
use App\Store\Domain\Repositories\StoreOrderRepositoryInterface;
use App\Store\Presentation\DTOs\CreateStoreOrderRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateStoreOrderService
{
    public function __construct(
        private readonly StoreOrderRepositoryInterface $repository,
        private readonly NotificationApplicationService $notificationService,
    ) {}

    public function execute(CreateStoreOrderRequest $dto, string $buyerId): StoreOrder
    {
        $stock = EloquentStock::with('harvest.farmer')->find($dto->stockId);

        if ($stock === null) {
            throw new \DomainException('Stock not found');
        }

        if ($stock->status !== 'in_stock') {
            throw new \DomainException('Stock is not available for order');
        }

        if ((float) $stock->quantity_kg < $dto->quantityKg) {
            throw new \DomainException('Order quantity exceeds available stock');
        }

        $basePrice = (float) ($stock->price_per_kg ?? 0);
        $isUrgent = (bool) $stock->is_urgent_sale;
        $flashActive = $isUrgent
            && (float) $stock->flash_discount_percent > 0
            && ($stock->flash_discount_expires_at === null || now()->isBefore($stock->flash_discount_expires_at));
        $discount = $flashActive ? (float) $stock->flash_discount_percent : 0;
        $effectivePrice = $basePrice * (1 - $discount / 100);

        $pricePerKg = $dto->pricePerKg ?? ($effectivePrice > 0 ? $effectivePrice : null);
        $totalAmount = $pricePerKg !== null ? round($dto->quantityKg * $pricePerKg, 2) : null;

        $order = StoreOrder::create(
            id: (string) Str::uuid(),
            stockId: $dto->stockId,
            buyerId: $buyerId,
            quantityKg: $dto->quantityKg,
            pricePerKg: $pricePerKg,
            totalAmount: $totalAmount,
            notes: $dto->notes,
            deliveryMethod: $dto->deliveryMethod,
            deliveryAddress: $dto->deliveryAddress,
            deliveryPhone: $dto->deliveryPhone,
            deliveryCity: $dto->deliveryCity,
            deliveryNotes: $dto->deliveryNotes,
        );

        $this->repository->save($order);

        // Mark stock as reserved while the order is active
        $stock->status = 'reserved';
        $stock->save();

        // Notify the farmer (seller) about the new order with buyer credentials + product image
        $farmer = $stock->harvest?->farmer;
        if ($farmer && $farmer->user_id) {
            $buyer = User::find($buyerId);
            $photoPath = $stock->photo_path ?: $stock->harvest?->photo_path;
            $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : null;

            $buyerName = $buyer?->name ?? 'Unknown buyer';
            $buyerPhone = $buyer?->phone ?? 'N/A';
            $buyerEmail = $buyer?->email ?? 'N/A';
            $cropName = $stock->crop_type;
            $qty = $dto->quantityKg;
            $total = $totalAmount !== null ? number_format($totalAmount, 0) . ' FCFA' : 'N/A';

            $message = "New order for {$qty} kg of {$cropName} ({$total}).\n\n";
            $message .= "Buyer: {$buyerName}\n";
            $message .= "Phone: {$buyerPhone}\n";
            $message .= "Email: {$buyerEmail}";
            if ($dto->deliveryCity) {
                $message .= "\nDelivery city: {$dto->deliveryCity}";
            }
            if ($dto->deliveryAddress) {
                $message .= "\nDelivery address: {$dto->deliveryAddress}";
            }
            $message .= "\n\nPlease confirm once you have received the payment from the buyer.";

            try {
                $this->notificationService->notify(
                    user: User::find($farmer->user_id),
                    type: NotificationType::fromString('system.alert'),
                    title: 'New store order · confirm payment',
                    message: $message,
                    deepLink: '/dashboard/farmer',
                    idempotencyKey: "store.order.created:{$order->getId()}",
                );
            } catch (\Throwable $e) {
                // Notification failure should not block order creation
            }
        }

        return $order;
    }
}
