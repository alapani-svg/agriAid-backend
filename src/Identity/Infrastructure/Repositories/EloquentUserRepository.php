<?php

namespace App\Identity\Infrastructure\Repositories;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\Email;
use App\Models\User as EloquentUser;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        $eloquentUser = EloquentUser::find($id);

        if (!$eloquentUser) {
            return null;
        }

        return $this->toDomain($eloquentUser);
    }

    public function findByEmail(string $email): ?User
    {
        $eloquentUser = EloquentUser::where('email', $email)->first();

        if (!$eloquentUser) {
            return null;
        }

        return $this->toDomain($eloquentUser);
    }

    public function save(User $user): void
    {
        EloquentUser::updateOrCreate(
            ['id' => $user->getId()],
            [
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'password' => $user->getPasswordHash(),
                'role' => $user->getRole(),
                'phone' => $user->getPhone(),
                'notification_preference' => $user->getNotificationPreference(),
            ]
        );
    }

    public function delete(User $user): void
    {
        EloquentUser::destroy($user->getId());
    }

    private function toDomain(EloquentUser $eloquentUser): User
    {
        return new User(
            id: $eloquentUser->id,
            name: $eloquentUser->name,
            email: Email::fromString($eloquentUser->email),
            passwordHash: $eloquentUser->password,
            role: $eloquentUser->role ?? 'farmer',
            phone: $eloquentUser->phone,
            notificationPreference: $eloquentUser->notification_preference ?? 'email',
            createdAt: \Carbon\CarbonImmutable::parse($eloquentUser->created_at),
            updatedAt: $eloquentUser->updated_at ? \Carbon\CarbonImmutable::parse($eloquentUser->updated_at) : null,
        );
    }
}