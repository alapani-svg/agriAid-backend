<?php

namespace App\Identity\Application\Commands;

class GenerateOTPCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $purpose = 'login',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            purpose: $data['purpose'] ?? 'login',
        );
    }
}
