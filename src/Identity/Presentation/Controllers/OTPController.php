<?php

namespace App\Identity\Presentation\Controllers;

use App\Identity\Application\Commands\GenerateOTPCommand;
use App\Identity\Application\Commands\VerifyOTPCommand;
use App\Identity\Application\Services\GenerateOTPService;
use App\Identity\Application\Services\VerifyOTPService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OTPController
{
    public function __construct(
        private readonly GenerateOTPService $generateOTPService,
        private readonly VerifyOTPService $verifyOTPService,
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'purpose' => 'nullable|in:login,password_reset,email_verification,phone_verification',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $command = GenerateOTPCommand::fromRequest($request->all());

        try {
            $otp = $this->generateOTPService->execute($command);

            return response()->json([
                'message' => 'OTP generated and sent successfully',
                'expires_at' => $otp->getExpiresAt()->toIso8601String(),
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|uuid',
            'code' => 'required|digits:6',
            'purpose' => 'nullable|in:login,password_reset,email_verification,phone_verification',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $command = VerifyOTPCommand::fromRequest($request->all());

        try {
            $result = $this->verifyOTPService->execute($command);

            return response()->json([
                'message' => 'OTP verified successfully',
                'token' => $result['token'],
                'user' => [
                    'id' => $result['user']->getId(),
                    'name' => $result['user']->getName(),
                    'email' => $result['user']->getEmail()->getValue(),
                ],
            ], 200);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
