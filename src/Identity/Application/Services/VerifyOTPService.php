<?php

namespace App\Identity\Application\Services;

use App\Identity\Application\Commands\VerifyOTPCommand;
use App\Identity\Domain\Entities\OTP;
use App\Identity\Domain\Repositories\OTPRepositoryInterface;
use App\Identity\Domain\ValueObjects\OTPCode;
use App\Identity\Domain\ValueObjects\OTPPurpose;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use Laravel\Sanctum\NewAccessToken;

class VerifyOTPService
{
    public function __construct(
        private readonly OTPRepositoryInterface $otpRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function execute(VerifyOTPCommand $command): array
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
            throw new \DomainException('User not found');
        }

        // Find the latest unverified OTP for this user and purpose
        $otp = $this->otpRepository->findLatestUnverified(
            userId: $command->userId,
            purpose: OTPPurpose::fromString($command->purpose),
        );

        if (!$otp) {
            throw new \DomainException('No valid OTP found');
        }

        // Verify the code matches
        $providedCode = OTPCode::fromString($command->code);
        if (!$otp->getCode()->equals($providedCode)) {
            throw new \DomainException('Invalid OTP code');
        }

        // Verify the OTP
        $otp->verify();

        // Save the verified OTP
        $this->otpRepository->save($otp);

        // Create Sanctum token for the user
        $token = $user->createToken('agriAid-auth-token');

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
        ];
    }
}
