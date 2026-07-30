<?php

namespace App\Identity\Domain\Repositories;

use App\Identity\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;

    public function delete(User $user): void;
}
