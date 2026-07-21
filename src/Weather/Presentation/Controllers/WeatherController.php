<?php

namespace Src\Weather\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Weather\Application\Services\WeatherApplicationService;
use Src\Weather\Application\DTOs\WeatherData;
use Src\Weather\Application\Queries\GetWeatherQuery;

class WeatherController
{
    public function __construct(
        private readonly WeatherApplicationService $weatherService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = GetWeatherQuery::fromRequest($request);
        $result = $this->weatherService->getWeatherData($query);

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $data = WeatherData::fromRequest($request);
        $result = $this->weatherService->recordWeatherData($data);

        return response()->json($result, 201);
    }

    public function show(string $id): JsonResponse
    {
        $result = $this->weatherService->getWeatherById($id);

        return response()->json($result);
    }
}
