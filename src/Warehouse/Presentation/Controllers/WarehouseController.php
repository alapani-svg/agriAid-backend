<?php

namespace App\Warehouse\Presentation\Controllers;

use App\Warehouse\Application\Services\RegisterWarehouseService;
use App\Warehouse\Domain\Entities\Warehouse;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController
{
    public function __construct(
        private readonly RegisterWarehouseService $registerWarehouseService,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly StockRepositoryInterface $stockRepository,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:60'],
            'capacity_total_kg' => ['required', 'numeric', 'gt:0'],
            'manager_user_id' => ['nullable', 'string', 'exists:users,id'],
            'farmer_id' => ['nullable', 'string', 'exists:users,id'],
            'village' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Farmer users can only create warehouses for themselves.
        // Warehouse managers and admins can create on behalf of any farmer.
        $farmerId = $data['farmer_id'];
        if ($user !== null && $user->role === 'farmer') {
            $farmerId = (string) $user->id;
        }

        $managerUserId = $data['manager_user_id'] ?? null;
        if ($user !== null && $user->role === 'warehouse') {
            $managerUserId = (string) $user->id;
        }

        $warehouse = $this->registerWarehouseService->execute(
            name: $data['name'],
            region: $data['region'],
            capacityTotalKg: (float) $data['capacity_total_kg'],
            managerUserId: $managerUserId,
            farmerId: $farmerId,
            village: $data['village'] ?? null,
            address: $data['address'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return response()->json($this->toArray($warehouse), 201);
    }

    public function updateAeration(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $warehouse = $this->warehouseRepository->findById($id);

        if ($warehouse === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $warehouse->setAerationActive((bool) $data['active']);
        $this->warehouseRepository->save($warehouse);

        return response()->json($this->toArray($warehouse));
    }

    /**
     * Assigns (or clears) the warehouse-role user responsible for this
     * warehouse. Admin-only: this is how a registered warehouse-role account
     * is granted access to a warehouse's manager console.
     */
    public function updateManager(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'manager_user_id' => ['nullable', 'string', 'exists:users,id'],
        ]);

        $warehouse = $this->warehouseRepository->findById($id);

        if ($warehouse === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $warehouse->assignManager($data['manager_user_id'] ?? null);
        $this->warehouseRepository->save($warehouse);

        return response()->json($this->toArray($warehouse));
    }

    /**
     * Assigns (or clears) the farmer who owns this warehouse.
     * Admin-only: ownership is the farmer's, not the warehouse manager's.
     */
    public function updateFarmer(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'farmer_id' => ['nullable', 'string', 'exists:users,id'],
        ]);

        $warehouse = $this->warehouseRepository->findById($id);

        if ($warehouse === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $warehouse->assignFarmer($data['farmer_id'] ?? null);
        $this->warehouseRepository->save($warehouse);

        return response()->json($this->toArray($warehouse));
    }

    public function mine(Request $request): JsonResponse
    {
        $userId = (string) $request->user()->id;
        $warehouses = $this->warehouseRepository->findByManagerUserId($userId);

        return response()->json([
            'data' => array_map(fn ($warehouse) => $this->toArray($warehouse), $warehouses),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $warehouse = $this->warehouseRepository->findById($id);

        if ($warehouse === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        return response()->json($this->toArray($warehouse));
    }

    public function index(Request $request): JsonResponse
    {
        $region = $request->query('region');
        $farmerId = $request->query('farmer_id');
        $isAdmin = $request->user()?->role === 'admin';

        $warehouses = match (true) {
            $farmerId !== null => $this->warehouseRepository->findByFarmerId($farmerId),
            $region !== null => $this->warehouseRepository->findByRegion($region),
            $isAdmin => $this->warehouseRepository->findAll(),
            default => $this->warehouseRepository->findAllActive(),
        };

        return response()->json([
            'data' => array_map(fn ($warehouse) => $this->toArray($warehouse), $warehouses),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Warehouse $warehouse): array
    {
        $capacityUsedKg = 0.0;
        foreach ($this->stockRepository->findByWarehouseId($warehouse->getId()) as $stock) {
            if ($stock->isInStock()) {
                $capacityUsedKg += $stock->getQuantityKg();
            }
        }

        $store = \App\Models\Store::where('warehouse_id', $warehouse->getId())->first();

        return [
            'id' => $warehouse->getId(),
            'manager_user_id' => $warehouse->getManagerUserId(),
            'farmer_id' => $warehouse->getFarmerId(),
            'name' => $warehouse->getName(),
            'region' => $warehouse->getRegion(),
            'village' => $warehouse->getVillage(),
            'address' => $warehouse->getAddress(),
            'capacity_total_kg' => $warehouse->getCapacityTotalKg(),
            'capacity_used_kg' => $capacityUsedKg,
            'capacity_available_kg' => $warehouse->getAvailableCapacity($capacityUsedKg),
            'status' => $warehouse->getStatus()->toString(),
            'aeration_active' => $warehouse->isAerationActive(),
            'aeration_updated_at' => $warehouse->getAerationUpdatedAt()?->format('Y-m-d H:i:s'),
            'notes' => $warehouse->getNotes(),
            'created_at' => $warehouse->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $warehouse->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'store' => $store ? [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'status' => $store->status,
            ] : null,
        ];
    }
}
