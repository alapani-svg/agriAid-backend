<?php

namespace App\Identity\Application\Services;

use App\Identity\Application\Commands\GenerateOTPCommand;
use App\Identity\Domain\Entities\OTP;
use App\Identity\Domain\Repositories\OTPRepositoryInterface;
use App\Identity\Domain\ValueObjects\OTPCode;
use App\Identity\Domain\ValueObjects\OTPPurpose;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Str;

class GenerateOTPService
{
    public function __construct(
        private readonly OTPRepositoryInterface $otpRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function execute(GenerateOTPCommand $command): OTP
    {
        $user = $this->userRepository->findById($command->userId);

        if (!$user) {
            throw new \DomainException('User not found');
        }

        // Generate a new OTP
        $otpCode = OTPCode::generate();
        $purpose = OTPPurpose::fromString($command->purpose);

        $otp = OTP::generate(
            id: Str::uuid()->toString(),
            userId: $command->userId,
            code: $otpCode,
            purpose: $purpose,
            expiresInMinutes: 10,
        );

        // Store the OTP
        $this->otpRepository->save($otp);

        // Send notification to user
        $user->notify(new \App\Identity\Notifications\SendOTPNotification($otp));

        return $otp;
    }
}
