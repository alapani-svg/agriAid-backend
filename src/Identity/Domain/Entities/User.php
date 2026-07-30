<?php

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\Events\UserRegistered;
use App\Identity\Domain\ValueObjects\Email;
use App\Shared\Domain\AggregateRoot;
use Illuminate\Notifications\Notifiable;

class User extends AggregateRoot
{
    use Notifiable;

    private function __construct(
        private readonly string $id,
        private string $name,
        private Email $email,
        private string $passwordHash,
        private string $role,
        private ?string $phone = null,
        private string $notificationPreference = 'email',
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public static function register(
        string $id,
        string $name,
        Email $email,
        string $passwordHash,
        string $role = 'farmer',
        ?string $phone = null,
        string $notificationPreference = 'email',
    ): self {
        $user = new self(
            $id,
            $name,
            $email,
            $passwordHash,
            $role,
            $phone,
            $notificationPreference,
            new \DateTimeImmutable(),
        );

        $user->recordEvent(new UserRegistered($user));

        return $user;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getNotificationPreference(): string
    {
        return $this->notificationPreference;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateRole(string $role): void
    {
        $this->role = $role;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateNotificationPreference(string $preference): void
    {
        $this->notificationPreference = $preference;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Laravel Notifiable interface methods
    public function routeNotificationForMail($notification)
    {
        return $this->email->getValue();
    }

    public function routeNotificationForVonage($notification)
    {
        return $this->phone;
    }
}
