<?php

namespace App\Notifications\Presentation\Requests;

readonly class CreateNotificationRequest
{
    public function __construct(
        public string $userId,
        public string $type,
        public string $title,
        public string $message,
        public ?string $deepLink = null,
        public ?string $idempotencyKey = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (string) ($data['user_id'] ?? ''),
            type: (string) ($data['type'] ?? 'system.alert'),
            title: (string) ($data['title'] ?? ''),
            message: (string) ($data['message'] ?? ''),
            deepLink: $data['deep_link'] ?? null,
            idempotencyKey: $data['idempotency_key'] ?? null,
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->userId)) {
            $errors['user_id'] = 'User ID is required';
        }

        if (empty($this->title)) {
            $errors['title'] = 'Title is required';
        }

        if (empty($this->message)) {
            $errors['message'] = 'Message is required';
        }

        return $errors;
    }
}
