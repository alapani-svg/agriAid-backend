<?php

namespace App\Farmer\Presentation\Controllers;

use App\Farmer\Application\Services\RegisterFarmerService;
use App\Farmer\Application\Services\UpdateFarmerProfileService;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Presentation\DTOs\RegisterFarmerRequest;
use App\Farmer\Presentation\DTOs\UpdateFarmerProfileRequest;
use App\Models\User;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Notifications\Domain\ValueObjects\NotificationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmerController
{
    public function __construct(
        private readonly RegisterFarmerService $registerFarmerService,
        private readonly UpdateFarmerProfileService $updateFarmerProfileService,
        private readonly FarmerRepositoryInterface $farmerRepository,
        private readonly NotificationApplicationService $notificationService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Farmers may only ever register a profile for themselves; ignore any
        // client-supplied user_id and force it to the authenticated user.
        if ($request->user()?->role === 'farmer') {
            $payload['user_id'] = $request->user()->id;
        }

        $dto = RegisterFarmerRequest::fromArray($payload);
        $errors = $dto->validate();

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        try {
            $farmer = $this->registerFarmerService->execute(
                userId: $dto->userId,
                farmName: $dto->farmName,
                farmSize: $dto->farmSize,
                crops: $dto->crops,
                region: $dto->region,
                village: $dto->village,
                phone: $dto->phone,
                address: $dto->address,
                cooperativeName: $dto->cooperativeName,
                cooperativeId: $dto->cooperativeId,
            );

            $user = User::find($farmer->getUserId());
            if ($user !== null) {
                $this->notificationService->notify(
                    user: $user,
                    type: NotificationType::FARMER_PROFILE_REGISTERED,
                    title: 'Farm profile created',
                    message: "Your farm \"{$farmer->getFarmName()}\" has been registered on agriAid.",
                    deepLink: '/dashboard/farmer/profile',
                    idempotencyKey: "farmer.registered:{$farmer->getId()}",
                );
            }

            return response()->json([
                'id' => $farmer->getId(),
                'user_id' => $farmer->getUserId(),
                'farm_name' => $farmer->getFarmName(),
                'farm_size' => $farmer->getFarmSize()->toHectares(),
                'crops' => $farmer->getCrops()->toArray(),
                'region' => $farmer->getRegion()->toString(),
                'village' => $farmer->getVillage(),
                'phone' => $farmer->getPhone(),
                'address' => $farmer->getAddress(),
                'cooperative_name' => $farmer->getCooperativeName(),
                'cooperative_id' => $farmer->getCooperativeId(),
                'status' => $farmer->getStatus()->toString(),
                'created_at' => $farmer->getCreatedAt()->format('Y-m-d H:i:s'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $farmers = $this->farmerRepository->findAll();

        return response()->json([
            'data' => array_map(fn ($farmer) => [
                'id' => $farmer->getId(),
                'user_id' => $farmer->getUserId(),
                'farm_name' => $farmer->getFarmName(),
                'farm_size' => $farmer->getFarmSize()->toHectares(),
                'crops' => $farmer->getCrops()->toArray(),
                'region' => $farmer->getRegion()->toString(),
                'village' => $farmer->getVillage(),
                'phone' => $farmer->getPhone(),
                'address' => $farmer->getAddress(),
                'cooperative_name' => $farmer->getCooperativeName(),
                'cooperative_id' => $farmer->getCooperativeId(),
                'status' => $farmer->getStatus()->toString(),
                'created_at' => $farmer->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $farmer->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ], $farmers),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $farmer = $this->farmerRepository->findById($id);

        if ($farmer === null) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        return response()->json([
            'id' => $farmer->getId(),
            'user_id' => $farmer->getUserId(),
            'farm_name' => $farmer->getFarmName(),
            'farm_size' => $farmer->getFarmSize()->toHectares(),
            'crops' => $farmer->getCrops()->toArray(),
            'region' => $farmer->getRegion()->toString(),
            'village' => $farmer->getVillage(),
            'phone' => $farmer->getPhone(),
            'address' => $farmer->getAddress(),
            'cooperative_name' => $farmer->getCooperativeName(),
            'cooperative_id' => $farmer->getCooperativeId(),
            'status' => $farmer->getStatus()->toString(),
            'created_at' => $farmer->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $farmer->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Admin lookup: resolves a farmer profile from a platform user id, so an
     * admin can jump from the user directory straight to that farmer's
     * credibility score without already knowing their farmer id.
     */
    public function showByUserId(string $userId): JsonResponse
    {
        $farmer = $this->farmerRepository->findByUserId($userId);

        if ($farmer === null) {
            return response()->json(['error' => 'Farmer profile not found'], 404);
        }

        return response()->json([
            'id' => $farmer->getId(),
            'user_id' => $farmer->getUserId(),
            'farm_name' => $farmer->getFarmName(),
            'farm_size' => $farmer->getFarmSize()->toHectares(),
            'crops' => $farmer->getCrops()->toArray(),
            'region' => $farmer->getRegion()->toString(),
            'village' => $farmer->getVillage(),
            'phone' => $farmer->getPhone(),
            'address' => $farmer->getAddress(),
            'cooperative_name' => $farmer->getCooperativeName(),
            'cooperative_id' => $farmer->getCooperativeId(),
            'status' => $farmer->getStatus()->toString(),
            'created_at' => $farmer->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $farmer->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $farmer = $this->farmerRepository->findByUserId($userId);

        if ($farmer === null) {
            return response()->json(['error' => 'Farmer profile not found'], 404);
        }

        return response()->json([
            'id' => $farmer->getId(),
            'user_id' => $farmer->getUserId(),
            'farm_name' => $farmer->getFarmName(),
            'farm_size' => $farmer->getFarmSize()->toHectares(),
            'crops' => $farmer->getCrops()->toArray(),
            'region' => $farmer->getRegion()->toString(),
            'village' => $farmer->getVillage(),
            'phone' => $farmer->getPhone(),
            'address' => $farmer->getAddress(),
            'cooperative_name' => $farmer->getCooperativeName(),
            'cooperative_id' => $farmer->getCooperativeId(),
            'status' => $farmer->getStatus()->toString(),
            'created_at' => $farmer->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $farmer->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $dto = UpdateFarmerProfileRequest::fromArray($request->all());

        try {
            $farmer = $this->updateFarmerProfileService->execute(
                farmerId: $id,
                farmName: $dto->farmName,
                farmSize: $dto->farmSize,
                crops: $dto->crops,
                region: $dto->region,
                village: $dto->village,
                phone: $dto->phone,
                address: $dto->address,
                cooperativeName: $dto->cooperativeName,
                cooperativeId: $dto->cooperativeId,
            );

            return response()->json([
                'id' => $farmer->getId(),
                'user_id' => $farmer->getUserId(),
                'farm_name' => $farmer->getFarmName(),
                'farm_size' => $farmer->getFarmSize()->toHectares(),
                'crops' => $farmer->getCrops()->toArray(),
                'region' => $farmer->getRegion()->toString(),
                'village' => $farmer->getVillage(),
                'phone' => $farmer->getPhone(),
                'address' => $farmer->getAddress(),
                'cooperative_name' => $farmer->getCooperativeName(),
                'cooperative_id' => $farmer->getCooperativeId(),
                'status' => $farmer->getStatus()->toString(),
                'updated_at' => $farmer->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
