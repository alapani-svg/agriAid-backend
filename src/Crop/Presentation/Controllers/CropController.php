<?php

namespace Src\Crop\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Crop\Application\Services\CropApplicationService;
use Src\Crop\Application\Commands\CreateCropCommand;
use Src\Crop\Application\Commands\UpdateCropCommand;
use Src\Crop\Application\Queries\GetCropQuery;

class CropController
{
    public function __construct(
        private readonly CropApplicationService $cropService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = GetCropQuery::fromRequest($request);
        $result = $this->cropService->getCrops($query);

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $command = CreateCropCommand::fromRequest($request);
        $result = $this->cropService->createCrop($command);

        return response()->json($result, 201);
    }

    public function show(string $id): JsonResponse
    {
        $result = $this->cropService->getCropById($id);

        return response()->json($result);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $command = UpdateCropCommand::fromRequest($request, $id);
        $result = $this->cropService->updateCrop($command);

        return response()->json($result);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->cropService->deleteCrop($id);

        return response()->json(['message' => 'Crop deleted successfully']);
    }
}
