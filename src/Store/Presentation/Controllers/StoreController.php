<?php

namespace App\Store\Presentation\Controllers;

use App\Models\Stock as EloquentStock;
use App\Models\Store as EloquentStore;
use App\Models\StoreOrder as EloquentStoreOrder;
use App\Services\AuditLogger;
use App\Store\Application\Services\CreateStoreOrderService;
use App\Store\Application\Services\ListAvailableStoreStockService;
use App\Store\Domain\Repositories\StoreOrderRepositoryInterface;
use App\Store\Domain\ValueObjects\StoreOrderStatus;
use App\Store\Presentation\DTOs\CreateStoreOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Admin: list ALL stock with full store details regardless of validation status.
     * GET /api/store/admin-all-stock
     */
    public function adminAllStock(Request $request): JsonResponse
    {
        $stocks = $this->listAvailableStoreStockService->executeForAdmin();

        return response()->json([
            'data' => $stocks,
        ]);
    }

    /**
     * Warehouse manager: list all stock pending validation.
     * GET /api/store/pending-validation
     */
    public function pendingValidation(Request $request): JsonResponse
    {
        $stocks = EloquentStock::with(['harvest.farmer.user', 'warehouse'])
            ->where('validation_status', 'pending')
            ->where('status', 'in_stock')
            ->orderByDesc('created_at')
            ->get();

        $items = $stocks->map(fn ($stock) => $this->validationItemArray($stock));

        return response()->json(['data' => $items]);
    }

    /**
     * Warehouse manager: list ALL stock with validation status (pending, approved, rejected).
     * GET /api/store/all-validation
     */
    public function allValidation(Request $request): JsonResponse
    {
        $query = EloquentStock::with(['harvest.farmer.user', 'warehouse'])
            ->where('status', 'in_stock')
            ->orderByDesc('created_at');

        // Optional status filter
        if ($status = $request->query('status')) {
            $query->where('validation_status', $status);
        }

        $stocks = $query->get();
        $items = $stocks->map(fn ($stock) => $this->validationItemArray($stock));

        return response()->json(['data' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validationItemArray($stock): array
    {
        $photoPath = $stock->photo_path ?: $stock->harvest?->photo_path;
        $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : null;
        $farmer = $stock->harvest?->farmer;

        return [
            'id' => $stock->id,
            'crop_type' => $stock->crop_type,
            'quantity_kg' => (float) $stock->quantity_kg,
            'quality_grade' => $stock->quality_grade,
            'origin' => $stock->origin,
            'warehouse_name' => $stock->warehouse?->name,
            'warehouse_region' => $stock->warehouse?->region,
            'harvest_date' => $stock->harvest?->harvest_date?->format('Y-m-d'),
            'photo_url' => $photoUrl,
            'verification_status' => $stock->verification_status ?? 'unavailable',
            'validation_status' => $stock->validation_status ?? 'pending',
            'validation_notes' => $stock->validation_notes,
            'validated_by' => $stock->validated_by,
            'validated_at' => $stock->validated_at?->toIso8601String(),
            'seller_name' => $farmer?->user?->name ?? 'Unknown',
            'farm_name' => $farmer?->farm_name,
            'seller_phone' => $farmer?->phone,
            'price_per_kg' => $stock->price_per_kg !== null ? (float) $stock->price_per_kg : null,
            'created_at' => $stock->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Warehouse manager: validate (approve/reject) a stock listing.
     * POST /api/store/stock/{id}/validate
     */
    public function validateStock(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Only warehouse managers and admins can validate
        if (!in_array($user->role, ['warehouse', 'admin'], true)) {
            return response()->json(['error' => 'Only warehouse managers and admins can validate stock'], 403);
        }

        $data = $request->validate([
            'decision' => ['required', 'string', 'in:pending,approved,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $stock = EloquentStock::find($id);
        if ($stock === null) {
            return response()->json(['error' => 'Stock not found'], 404);
        }

        // Allow re-validation (e.g. setting back to pending from approved/rejected)
        $stock->validation_status = $data['decision'];
        $stock->validated_by = $data['decision'] === 'pending' ? null : $user->id;
        $stock->validated_at = $data['decision'] === 'pending' ? null : now();
        $stock->validation_notes = $data['notes'] ?? null;
        $stock->save();

        AuditLogger::log(
            action: 'stock.validated',
            category: 'store',
            metadata: ['stock_id' => $stock->id, 'decision' => $data['decision'], 'validated_by' => $user->id],
        );

        // Notify the farmer about the validation result
        $farmer = $stock->harvest?->farmer;
        if ($farmer && $farmer->user_id) {
            $cropName = $stock->crop_type;
            $decision = $data['decision'];
            $message = $decision === 'approved'
                ? "Your stock of {$cropName} ({$stock->quantity_kg} kg) has been approved by the warehouse manager and is now eligible for sale on the store."
                : "Your stock of {$cropName} ({$stock->quantity_kg} kg) was rejected by the warehouse manager." . ($data['notes'] ? " Reason: {$data['notes']}" : '');

            try {
                app(\App\Notifications\Application\Services\NotificationApplicationService::class)->notify(
                    user: \App\Models\User::find($farmer->user_id),
                    type: \App\Notifications\Domain\ValueObjects\NotificationType::fromString('system.alert'),
                    title: $decision === 'approved' ? 'Stock approved for sale' : 'Stock validation rejected',
                    message: $message,
                    deepLink: '/dashboard/farmer',
                    idempotencyKey: "stock.validation:{$stock->id}:{$decision}",
                );
            } catch (\Throwable $e) {
                // Notification failure should not block the validation
            }
        }

        return response()->json([
            'id' => $stock->id,
            'validation_status' => $stock->validation_status,
            'validated_at' => $stock->validated_at?->toIso8601String(),
            'message' => $data['decision'] === 'approved'
                ? 'Stock approved and now eligible for sale on the store.'
                : 'Stock rejected.',
        ]);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Buyers see their own orders; farmers see orders for their stock
        if ($user->role === 'farmer') {
            // Find all stock owned by this farmer, then get orders for those stocks
            $farmerStocks = EloquentStock::whereHas('harvest.farmer', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->pluck('id');

            $orders = EloquentStoreOrder::whereIn('stock_id', $farmerStocks)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($eloquent) => $this->storeOrderRepository->findById($eloquent->id))
                ->filter()
                ->toArray();

            return response()->json([
                'data' => array_map(fn ($order) => $this->toArray($order, true), $orders),
            ]);
        }

        $orders = $this->storeOrderRepository->findByBuyerId($user->id);

        return response()->json([
            'data' => array_map(fn ($order) => $this->toArray($order, true), $orders),
        ]);
    }

    public function adminOrders(Request $request): JsonResponse
    {
        $orders = $this->storeOrderRepository->findAll();

        return response()->json([
            'data' => array_map(fn ($order) => $this->toArray($order, true), $orders),
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

            AuditLogger::log(
                action: 'order.created',
                category: 'store',
                metadata: ['order_id' => $order->getId(), 'stock_id' => $order->getStockId(), 'buyer_id' => $user->id, 'quantity_kg' => $order->getQuantityKg(), 'total_amount' => $order->getTotalAmount()],
            );

            return response()->json($this->toArray($order, true), 201);
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

        return response()->json($this->toArray($order, true));
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $status = $request->input('status');
        $validStatuses = ['farmer_confirmed', 'confirmed', 'shipped', 'delivered', 'completed', 'cancelled'];

        if (empty($status) || !in_array($status, $validStatuses, true)) {
            return response()->json(['error' => 'Valid status is required (' . implode(', ', $validStatuses) . ')'], 422);
        }

        $order = $this->storeOrderRepository->findById($id);

        if ($order === null) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $user = Auth::user();
        $isAdmin = $user !== null && $user->role === 'admin';
        $isFarmer = $user !== null && $user->role === 'farmer';
        $isBuyer = $user !== null && $order->getBuyerId() === $user->id;

        // Permission rules:
        // - farmer_confirmed: only the seller farmer can confirm payment received
        // - confirmed/shipped: admin or farmer
        // - delivered: buyer confirms receipt of goods
        // - completed: buyer validates receipt (final confirmation)
        // - cancelled: buyer (own pending), admin, or farmer
        if ($status === 'farmer_confirmed') {
            // Verify the user is the farmer who owns this stock
            $stock = EloquentStock::with('harvest.farmer')->find($order->getStockId());
            $orderFarmer = $stock?->harvest?->farmer;
            if (!$isFarmer || $orderFarmer === null || $orderFarmer->user_id !== $user?->id) {
                if (!$isAdmin) {
                    return response()->json(['error' => 'Only the seller farmer can confirm payment receipt'], 403);
                }
            }
        } elseif ($status === 'delivered' || $status === 'completed') {
            if (!$isBuyer && !$isAdmin) {
                return response()->json(['error' => 'Only the buyer can confirm receipt of goods'], 403);
            }
        } elseif ($status === 'cancelled') {
            if (!$isBuyer && !$isAdmin && !$isFarmer) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            if (!$isAdmin && !$isFarmer) {
                return response()->json(['error' => 'Only farmers and admins can update order status'], 403);
            }
        }

        try {
            if ($status === 'farmer_confirmed') {
                $order->farmerConfirm();
            } elseif ($status === 'confirmed') {
                $order->confirm();
            } elseif ($status === 'shipped') {
                $order->ship();
            } elseif ($status === 'delivered') {
                $order->deliver();
            } elseif ($status === 'completed') {
                $order->complete();
                // On completion, mark the stock as sold
                $this->markStockSold($order->getStockId());
            } elseif ($status === 'cancelled') {
                $order->cancel();
                // On cancellation, restore the stock to in_stock
                $this->restoreStock($order->getStockId());
            }

            $this->storeOrderRepository->save($order);

            return response()->json($this->toArray($order, true));
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
     * Farmer-facing: publish a harvested stock for sale by setting a price.
     * The stock's harvest photo is reused as the store listing image.
     */
    public function publishForSale(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'price_per_kg' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:10'],
            'unit' => ['nullable', 'string', 'max:30'],
            'unit_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'quality_grade' => ['nullable', 'string', 'max:60'],
            'origin' => ['nullable', 'string', 'max:120'],
            'is_urgent_sale' => ['boolean'],
            'flash_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'flash_discount_expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $stock = EloquentStock::with('harvest.farmer')->find($id);

        if ($stock === null) {
            return response()->json(['error' => 'Stock not found'], 404);
        }

        $farmer = $stock->harvest?->farmer;
        if ($farmer === null || $farmer->user_id !== $user->id) {
            return response()->json(['error' => 'You do not own this stock'], 403);
        }

        if ($stock->status !== 'in_stock' || (float) $stock->quantity_kg <= 0) {
            return response()->json(['error' => 'This stock is not available for sale'], 422);
        }

        if (empty($stock->photo_path) && $stock->harvest?->photo_path) {
            $stock->photo_path = $stock->harvest->photo_path;
        }

        $stock->price_per_kg = $data['price_per_kg'];
        $stock->currency = $data['currency'] ?? 'FCFA';
        $stock->unit = $data['unit'] ?? 'kg';
        $stock->unit_weight_kg = $data['unit_weight_kg'] ?? $stock->unit_weight_kg;
        $stock->quality_grade = $data['quality_grade'] ?? $stock->quality_grade;
        $stock->origin = $data['origin'] ?? $stock->origin;
        $stock->seller_id = $farmer->id;
        $stock->is_urgent_sale = $data['is_urgent_sale'] ?? false;
        $stock->flash_discount_percent = $data['flash_discount_percent'] ?? 0;
        $stock->flash_discount_expires_at = $data['flash_discount_expires_at'] ?? null;
        $stock->notes = $data['notes'] ?? $stock->notes;
        // Link the stock to the farmer's store if they have one
        $farmerStore = EloquentStore::where('farmer_id', $farmer->user_id)->first();
        if ($farmerStore) {
            $stock->store_id = $farmerStore->id;
        }
        // Preserve existing validation status if already approved by warehouse manager.
        // Only reset to pending if this is the first time publishing or it was rejected.
        if ($stock->validation_status !== 'approved') {
            $stock->validation_status = 'pending';
            $stock->validated_by = null;
            $stock->validated_at = null;
            $stock->validation_notes = null;
        }
        $stock->save();

        $photoUrl = null;
        if ($stock->photo_path) {
            $photoUrl = Storage::disk('public')->url($stock->photo_path);
        }

        AuditLogger::log(
            action: 'stock.published_for_sale',
            category: 'store',
            metadata: ['stock_id' => $stock->id, 'crop_type' => $stock->crop_type, 'price_per_kg' => $stock->price_per_kg, 'store_id' => $stock->store_id],
        );

        return response()->json([
            'id' => $stock->id,
            'crop_type' => $stock->crop_type,
            'quantity_kg' => (float) $stock->quantity_kg,
            'price_per_kg' => (float) $stock->price_per_kg,
            'currency' => $stock->currency,
            'unit' => $stock->unit,
            'is_urgent_sale' => (bool) $stock->is_urgent_sale,
            'photo_url' => $photoUrl,
            'message' => 'Listing published to the store',
        ]);
    }

    /**
     * Farmer-facing: list the authenticated farmer's own stock entries.
     */
    public function myStock(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $stocks = EloquentStock::with(['harvest.farmer', 'warehouse'])
            ->whereHas('harvest.farmer', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get();

        $items = $stocks->map(function ($stock) {
            $photoPath = $stock->photo_path ?: $stock->harvest?->photo_path;
            $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : null;

            return [
                'id' => $stock->id,
                'crop_type' => $stock->crop_type,
                'quantity_kg' => (float) $stock->quantity_kg,
                'status' => $stock->status,
                'price_per_kg' => $stock->price_per_kg !== null ? (float) $stock->price_per_kg : null,
                'currency' => $stock->currency ?? 'FCFA',
                'is_urgent_sale' => (bool) $stock->is_urgent_sale,
                'warehouse_name' => $stock->warehouse?->name,
                'harvest_date' => $stock->harvest?->harvest_date?->format('Y-m-d'),
                'photo_url' => $photoUrl,
                'verification_status' => $stock->verification_status ?? 'unavailable',
                'validation_status' => $stock->validation_status ?? 'pending',
                'validation_notes' => $stock->validation_notes,
                'is_listed_for_sale' => $stock->price_per_kg !== null && $stock->status === 'in_stock' && (float) $stock->quantity_kg > 0,
                'is_approved_for_sale' => $stock->validation_status === 'approved',
                'created_at' => $stock->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return response()->json(['data' => $items]);
    }

    /**
     * Farmer-facing: create the farmer's own store.
     * POST /api/store/create
     */
    public function createStore(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        // A farmer can only have one store
        $existing = EloquentStore::where('farmer_id', $user->id)->first();
        if ($existing !== null) {
            return response()->json(['error' => 'You already have a store'], 422);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('store-logos', 'public');
        }

        $store = EloquentStore::create([
            'farmer_id' => $user->id,
            'warehouse_id' => null,
            'name' => $data['store_name'],
            'description' => $data['description'] ?? null,
            'logo_path' => $logoPath,
            'status' => 'active',
            'theme_color' => '#026e00',
        ]);

        AuditLogger::log(
            action: 'store.created',
            category: 'store',
            metadata: ['store_id' => $store->id, 'store_name' => $store->name, 'farmer_id' => $user->id],
        );

        return response()->json($this->storeToArray($store, []), 201);
    }

    /**
     * Farmer-facing: get the farmer's own store with its produce listings.
     * GET /api/store/my-store
     */
    public function myStore(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $store = EloquentStore::where('farmer_id', $user->id)->first();

        if ($store === null) {
            return response()->json(['data' => null], 200);
        }

        // Get all stock items listed in this farmer's store
        $stocks = EloquentStock::with(['harvest.farmer', 'warehouse'])
            ->where('store_id', $store->id)
            ->orderByDesc('created_at')
            ->get();

        $produce = $stocks->map(function ($stock) {
            $photoPath = $stock->photo_path ?: $stock->harvest?->photo_path;
            $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : null;

            return [
                'id' => $stock->id,
                'crop_type' => $stock->crop_type,
                'quantity_kg' => (float) $stock->quantity_kg,
                'status' => $stock->status,
                'price_per_kg' => $stock->price_per_kg !== null ? (float) $stock->price_per_kg : null,
                'currency' => $stock->currency ?? 'FCFA',
                'unit' => $stock->unit,
                'quality_grade' => $stock->quality_grade,
                'origin' => $stock->origin,
                'is_urgent_sale' => (bool) $stock->is_urgent_sale,
                'warehouse_name' => $stock->warehouse?->name,
                'harvest_date' => $stock->harvest?->harvest_date?->format('Y-m-d'),
                'photo_url' => $photoUrl,
                'validation_status' => $stock->validation_status ?? 'pending',
                'is_listed_for_sale' => $stock->price_per_kg !== null && $stock->status === 'in_stock' && (float) $stock->quantity_kg > 0,
                'is_approved_for_sale' => $stock->validation_status === 'approved',
                'created_at' => $stock->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return response()->json([
            'data' => $this->storeToArray($store, $produce),
        ]);
    }

    /**
     * Farmer-facing: update the farmer's own store.
     * PUT /api/store/my-store
     */
    public function updateMyStore(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'store_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $store = EloquentStore::where('farmer_id', $user->id)->first();

        if ($store === null) {
            return response()->json(['error' => 'You do not have a store yet'], 404);
        }

        if (array_key_exists('store_name', $data)) {
            $store->name = $data['store_name'];
        }
        if (array_key_exists('description', $data)) {
            $store->description = $data['description'] ?? null;
        }

        if ($request->hasFile('logo')) {
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $store->logo_path = $request->file('logo')->store('store-logos', 'public');
        }

        $store->save();

        // Return with produce listings
        $stocks = EloquentStock::with(['harvest.farmer', 'warehouse'])
            ->where('store_id', $store->id)
            ->orderByDesc('created_at')
            ->get();

        $produce = $stocks->map(function ($stock) {
            $photoPath = $stock->photo_path ?: $stock->harvest?->photo_path;
            $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : null;

            return [
                'id' => $stock->id,
                'crop_type' => $stock->crop_type,
                'quantity_kg' => (float) $stock->quantity_kg,
                'status' => $stock->status,
                'price_per_kg' => $stock->price_per_kg !== null ? (float) $stock->price_per_kg : null,
                'currency' => $stock->currency ?? 'FCFA',
                'unit' => $stock->unit,
                'quality_grade' => $stock->quality_grade,
                'origin' => $stock->origin,
                'is_urgent_sale' => (bool) $stock->is_urgent_sale,
                'warehouse_name' => $stock->warehouse?->name,
                'harvest_date' => $stock->harvest?->harvest_date?->format('Y-m-d'),
                'photo_url' => $photoUrl,
                'validation_status' => $stock->validation_status ?? 'pending',
                'is_listed_for_sale' => $stock->price_per_kg !== null && $stock->status === 'in_stock' && (float) $stock->quantity_kg > 0,
                'is_approved_for_sale' => $stock->validation_status === 'approved',
                'created_at' => $stock->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return response()->json([
            'data' => $this->storeToArray($store, $produce),
        ]);
    }

    /**
     * Mark stock as sold (called when an order completes).
     */
    private function markStockSold(string $stockId): void
    {
        $stock = EloquentStock::find($stockId);
        if ($stock !== null) {
            $stock->status = 'sold';
            $stock->save();
        }
    }

    /**
     * Restore stock to in_stock when an order is cancelled.
     */
    private function restoreStock(string $stockId): void
    {
        $stock = EloquentStock::find($stockId);
        if ($stock !== null && $stock->status === 'reserved') {
            $stock->status = 'in_stock';
            $stock->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function storeToArray(EloquentStore $store, array $produce): array
    {
        $logoUrl = $store->logo_path ? Storage::disk('public')->url($store->logo_path) : null;

        return [
            'id' => $store->id,
            'farmer_id' => $store->farmer_id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'logo_url' => $logoUrl,
            'status' => $store->status,
            'theme_color' => $store->theme_color,
            'produce' => $produce,
            'created_at' => $store->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $store->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(\App\Store\Domain\Entities\StoreOrder $order, bool $includeDetails = false): array
    {
        $data = [
            'id' => $order->getId(),
            'stock_id' => $order->getStockId(),
            'buyer_id' => $order->getBuyerId(),
            'quantity_kg' => $order->getQuantityKg(),
            'price_per_kg' => $order->getPricePerKg(),
            'total_amount' => $order->getTotalAmount(),
            'status' => $order->getStatus()->toString(),
            'notes' => $order->getNotes(),
            'delivery_method' => $order->getDeliveryMethod(),
            'delivery_address' => $order->getDeliveryAddress(),
            'delivery_city' => $order->getDeliveryCity(),
            'delivery_phone' => $order->getDeliveryPhone(),
            'delivery_notes' => $order->getDeliveryNotes(),
            'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($includeDetails) {
            // Enrich with stock + seller info
            $stock = EloquentStock::with(['harvest.farmer.user', 'warehouse'])->find($order->getStockId());
            if ($stock !== null) {
                $photoPath = $stock->photo_path ?: $stock->harvest?->photo_path;
                $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : null;
                $farmer = $stock->harvest?->farmer;

                $data['stock'] = [
                    'crop_type' => $stock->crop_type,
                    'quantity_kg' => (float) $stock->quantity_kg,
                    'quality_grade' => $stock->quality_grade,
                    'origin' => $stock->origin,
                    'warehouse_name' => $stock->warehouse?->name,
                    'warehouse_region' => $stock->warehouse?->region,
                    'photo_url' => $photoUrl,
                    'currency' => $stock->currency ?? 'FCFA',
                ];
                $data['seller'] = [
                    'name' => $farmer?->user?->name ?? 'Unknown',
                    'farm_name' => $farmer?->farm_name,
                    'phone' => $farmer?->phone,
                ];
            }

            // Buyer info
            $buyer = \App\Models\User::find($order->getBuyerId());
            $data['buyer'] = [
                'name' => $buyer?->name ?? 'Unknown',
                'email' => $buyer?->email,
                'phone' => $buyer?->phone,
            ];
        }

        return $data;
    }
}
