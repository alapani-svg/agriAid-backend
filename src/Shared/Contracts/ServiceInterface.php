<?php

namespace Src\Shared\Contracts;

interface ServiceInterface
{
    public function create(array $data): mixed;

    public function update(int|string $id, array $data): mixed;

    public function delete(int|string $id): bool;
}
