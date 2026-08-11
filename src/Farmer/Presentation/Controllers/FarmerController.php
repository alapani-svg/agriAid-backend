<?php

namespace App\Farmer\Presentation\Controllers;

use App\Farmer\Application\Services\RegisterFarmerService;
use App\Farmer\Application\Services\UpdateFarmerProfileService;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Presentation\DTOs\RegisterFarmerRequest;
use App\Farmer\Presentation\DTOs\UpdateFarmerProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmerController
{
    public function __construct(
        private readonly RegisterFarmerService $registerFarmerService,
        private readonly UpdateFarmerProfileService $updateFarmerProfileService,
        private readonly FarmerRepositoryInterface $farmerRepository
    ) {}

    public function register(Request $request): JsonResponse
    {
        $dto = RegisterFarmerRequest::fromArray($request->all());
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
