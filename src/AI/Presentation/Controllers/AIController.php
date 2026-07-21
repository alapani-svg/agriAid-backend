<?php

namespace Src\AI\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\AI\Application\Services\AIApplicationService;
use Src\AI\Application\Commands\GeneratePredictionCommand;
use Src\AI\Application\Commands\GenerateRecommendationCommand;
use Src\AI\Application\Queries\GetPredictionQuery;

class AIController
{
    public function __construct(
        private readonly AIApplicationService $aiService
    ) {}

    public function predict(Request $request): JsonResponse
    {
        $command = GeneratePredictionCommand::fromRequest($request);
        $result = $this->aiService->generatePrediction($command);

        return response()->json($result, 201);
    }

    public function recommend(Request $request): JsonResponse
    {
        $command = GenerateRecommendationCommand::fromRequest($request);
        $result = $this->aiService->generateRecommendation($command);

        return response()->json($result, 201);
    }

    public function show(string $id): JsonResponse
    {
        $query = new GetPredictionQuery($id);
        $result = $this->aiService->getPrediction($query);

        return response()->json($result);
    }
}
