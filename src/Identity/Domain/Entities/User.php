<?php

namespace Src\Identity\Domain\Entities;

use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\ValueObjects\Email;
use Src\Shared\Contracts\AggregateRoot;

class User extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private string $name,
        private Email $email,
        private string $passwordHash,
        private string $role,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public static function register(
        string $id,
        string $name,
        Email $email,
        string $passwordHash,
        string $role = 'farmer',
    ): self {
        $user = new self(
            $id,
            $name,
            $email,
            $passwordHash,
            $role,
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
}
