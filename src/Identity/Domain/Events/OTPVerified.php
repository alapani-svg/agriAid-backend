<?php

namespace App\Identity\Domain\Events;

use App\Identity\Domain\Entities\OTP;
use App\Shared\Domain\DomainEvent;

class OTPVerified implements DomainEvent
{
    public function __construct(
        private readonly OTP $otp,
    ) {}

    public function getOTP(): OTP
    {
        return $this->otp;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->otp->getVerifiedAt() ?? $this->otp->getCreatedAt();
    }
}
