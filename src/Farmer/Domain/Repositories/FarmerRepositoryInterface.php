<?php

namespace App\Farmer\Domain\Repositories;

use App\Farmer\Domain\Entities\Farmer;
use App\Farmer\Domain\ValueObjects\Region;

interface FarmerRepositoryInterface
{
    public function save(Farmer $farmer): void;
    
    public function findById(string $id): ?Farmer;
    
    public function findByUserId(string $userId): ?Farmer;
    
    public function findByRegion(Region $region): array;
    
    public function findAllActive(): array;
    
    public function findAll(): array;
    
    public function delete(Farmer $farmer): void;
    
    public function existsByUserId(string $userId): bool;
}
