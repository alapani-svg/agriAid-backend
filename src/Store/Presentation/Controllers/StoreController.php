<?php

namespace App\Store\Presentation\Controllers;

use App\Models\Stock as EloquentStock;
use App\Store\Application\Services\CreateStoreOrderService;
use App\Store\Application\Services\ListAvailableStoreStockService;
use App\Store\Domain\Repositories\StoreOrderRepositoryInterface;
use App\Store\Domain\ValueObjects\StoreOrderStatus;
use App\Store\Presentation\DTOs\CreateStoreOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController
{
    public function __construct(
        private readonly CreateStoreOrderService $createStoreOrderService,
        private readonly ListAvailableStoreStockService $listAvailableStoreStockService,
        private readonly StoreOrderRepositoryInterface $storeOrderRepository,
    ) {}

    public function availableStock(Request $request): JsonResponse
    {
        $stocks = $this->listAvailableStoreStockService->execute();

        return response()->json([
            'data' => $stocks,
        ]);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $orders = $this->storeOrderRepository->findByBuyerId($user->id);

        return response()->json([
            'data' => array_map(fn ($order) => $this->toArray($order), $orders),
        ]);
    }

    public function adminOrders(Request $request): JsonResponse
    {
        $orders = $this->storeOrderRepository->findAll();

        return response()->json([
            'data' => array_map(fn ($order) => $this->toArray($order), $orders),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $dto = CreateStoreOrderRequest::fromArray($request->all());
        $errors = $dto->validate();

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        try {
            $order = $this->createStoreOrderService->execute($dto, $user->id);

            return response()->json($this->toArray($order), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->storeOrderRepository->findById($id);

        if ($order === null) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($this->toArray($order));
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $status = $request->input('status');

        if (empty($status) || !in_array($status, ['confirmed', 'cancelled', 'completed'], true)) {
            return response()->json(['error' => 'Valid status is required (confirmed, cancelled, completed)'], 422);
        }

        $order = $this->storeOrderRepository->findById($id);

        if ($order === null) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        try {
            if ($status === 'confirmed') {
                $order->confirm();
            } elseif ($status === 'cancelled') {
                $order->cancel();
            } else {
                $order->complete();
            }

            $this->storeOrderRepository->save($order);

            return response()->json($this->toArray($order));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateStockListing(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'variety' => ['nullable', 'string', 'max:120'],
            'price_per_kg' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'unit' => ['nullable', 'string', 'max:30'],
            'unit_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'price_tiers' => ['nullable', 'array'],
            'quality_grade' => ['nullable', 'string', 'max:60'],
            'origin' => ['nullable', 'string', 'max:120'],
            'seller_id' => ['nullable', 'string'],
            'is_urgent_sale' => ['boolean'],
            'flash_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'flash_discount_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $stock = EloquentStock::find($id);

        if ($stock === null) {
            return response()->json(['error' => 'Stock not found'], 404);
        }

        foreach (['variety', 'price_per_kg', 'currency', 'unit', 'unit_weight_kg', 'price_tiers', 'quality_grade', 'origin', 'seller_id', 'is_urgent_sale', 'flash_discount_percent', 'flash_discount_expires_at', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $stock->{$field} = $data[$field] ?? null;
            }
        }

        $stock->save();

        return response()->json(['message' => 'Stock listing updated']);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(\App\Store\Domain\Entities\StoreOrder $order): array
    {
        return [
            'id' => $order->getId(),
            'stock_id' => $order->getStockId(),
            'buyer_id' => $order->getBuyerId(),
            'quantity_kg' => $order->getQuantityKg(),
            'price_per_kg' => $order->getPricePerKg(),
            'total_amount' => $order->getTotalAmount(),
            'status' => $order->getStatus()->toString(),
            'notes' => $order->getNotes(),
            'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
