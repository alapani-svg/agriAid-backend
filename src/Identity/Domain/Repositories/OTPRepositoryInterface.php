<?php

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\OTP;
use App\Identity\Domain\ValueObjects\OTPPurpose;

interface OTPRepositoryInterface
{
    public function save(OTP $otp): void;

    public function findById(string $id): ?OTP;

    public function findLatestUnverified(string $userId, OTPPurpose $purpose): ?OTP;

    public function deleteExpired(): int;
}
