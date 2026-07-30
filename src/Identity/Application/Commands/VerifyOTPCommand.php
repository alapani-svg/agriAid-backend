<?php

namespace App\Identity\Application\Commands;

class VerifyOTPCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $code,
        public readonly string $purpose = 'login',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            code: $data['code'],
            purpose: $data['purpose'] ?? 'login',
        );
    }
}
