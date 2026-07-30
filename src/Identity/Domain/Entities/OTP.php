<?php

namespace App\Identity\Domain\Entities;

use App\Shared\Domain\AggregateRoot;
use App\Identity\Domain\Events\OTPGenerated;
use App\Identity\Domain\Events\OTPVerified;
use App\Identity\Domain\ValueObjects\OTPCode;
use App\Identity\Domain\ValueObjects\OTPPurpose;
use Carbon\CarbonImmutable;

class OTP extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly OTPCode $code,
        private readonly OTPPurpose $purpose,
        private readonly CarbonImmutable $expiresAt,
        private bool $verified = false,
        private ?CarbonImmutable $verifiedAt = null,
        private readonly CarbonImmutable $createdAt,
    ) {}

    public static function generate(
        string $id,
        string $userId,
        OTPCode $code,
        OTPPurpose $purpose,
        int $expiresInMinutes = 10,
    ): self {
        $otp = new self(
            id: $id,
            userId: $userId,
            code: $code,
            purpose: $purpose,
            expiresAt: CarbonImmutable::now()->addMinutes($expiresInMinutes),
            createdAt: CarbonImmutable::now(),
        );

        $otp->recordEvent(new OTPGenerated($otp));

        return $otp;
    }

    public function verify(): void
    {
        if ($this->verified) {
            throw new \DomainException('OTP has already been verified');
        }

        if ($this->isExpired()) {
            throw new \DomainException('OTP has expired');
        }

        $this->verified = true;
        $this->verifiedAt = CarbonImmutable::now();

        $this->recordEvent(new OTPVerified($this));
    }

    public function isExpired(): bool
    {
        return CarbonImmutable::now()->isAfter($this->expiresAt);
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCode(): OTPCode
    {
        return $this->code;
    }

    public function getPurpose(): OTPPurpose
    {
        return $this->purpose;
    }

    public function getExpiresAt(): CarbonImmutable
    {
        return $this->expiresAt;
    }

    public function getVerifiedAt(): ?CarbonImmutable
    {
        return $this->verifiedAt;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }
}
