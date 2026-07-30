<?php

namespace App\Identity\Infrastructure\Persistence;

use App\Identity\Domain\Entities\OTP;
use App\Identity\Domain\Repositories\OTPRepositoryInterface;
use App\Identity\Domain\ValueObjects\OTPPurpose;
use App\Identity\Infrastructure\Persistence\EloquentModels\EloquentOTP;
use Illuminate\Support\Facades\DB;

class EloquentOTPRepository implements OTPRepositoryInterface
{
    public function save(OTP $otp): void
    {
        $eloquentOTP = EloquentOTP::updateOrCreate(
            ['id' => $otp->getId()],
            [
                'user_id' => $otp->getUserId(),
                'code' => $otp->getCode()->getValue(),
                'purpose' => $otp->getPurpose()->value,
                'expires_at' => $otp->getExpiresAt(),
                'verified' => $otp->isVerified(),
                'verified_at' => $otp->getVerifiedAt(),
                'created_at' => $otp->getCreatedAt(),
            ]
        );
    }

    public function findById(string $id): ?OTP
    {
        $eloquentOTP = EloquentOTP::find($id);

        if (!$eloquentOTP) {
            return null;
        }

        return $this->toDomain($eloquentOTP);
    }

    public function findLatestUnverified(string $userId, OTPPurpose $purpose): ?OTP
    {
        $eloquentOTP = EloquentOTP::where('user_id', $userId)
            ->where('purpose', $purpose->value)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$eloquentOTP) {
            return null;
        }

        return $this->toDomain($eloquentOTP);
    }

    public function deleteExpired(): int
    {
        return EloquentOTP::where('expires_at', '<', now())->delete();
    }

    private function toDomain(EloquentOTP $eloquentOTP): OTP
    {
        return new OTP(
            id: $eloquentOTP->id,
            userId: $eloquentOTP->user_id,
            code: \App\Identity\Domain\ValueObjects\OTPCode::fromString($eloquentOTP->code),
            purpose: OTPPurpose::fromString($eloquentOTP->purpose),
            expiresAt: \Carbon\CarbonImmutable::parse($eloquentOTP->expires_at),
            verified: $eloquentOTP->verified,
            verifiedAt: $eloquentOTP->verified_at ? \Carbon\CarbonImmutable::parse($eloquentOTP->verified_at) : null,
            createdAt: \Carbon\CarbonImmutable::parse($eloquentOTP->created_at),
        );
    }
}
