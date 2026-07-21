<?php

namespace Src\Identity\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Identity\Application\Services\AuthApplicationService;
use Src\Identity\Application\DTOs\LoginData;
use Src\Identity\Application\DTOs\RegisterData;

class AuthController
{
    public function __construct(
        private readonly AuthApplicationService $authService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = RegisterData::fromRequest($request);
        $result = $this->authService->register($data);

        return response()->json($result, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = LoginData::fromRequest($request);
        $result = $this->authService->login($data);

        return response()->json($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
