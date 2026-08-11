<?php

namespace App\Providers;

use App\Identity\Domain\Repositories\OTPRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Infrastructure\Persistence\EloquentOTPRepository;
use App\Identity\Infrastructure\Persistence\EloquentUserRepository;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Infrastructure\Persistence\EloquentFarmerRepository;
use App\Farmer\Application\Services\RegisterFarmerService;
use App\Farmer\Application\Services\UpdateFarmerProfileService;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Infrastructure\Persistence\EloquentHarvestRepository;
use App\Farm\Application\Services\RecordHarvestService;
use App\Farm\Application\Services\SendHarvestToWarehouseService;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Infrastructure\Persistence\EloquentStockRepository;
use App\Stock\Application\Services\CreateStockService;
use App\Stock\Application\Services\AutoUpdateStockOnHarvestStored;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Identity
        $this->app->bind(OTPRepositoryInterface::class, EloquentOTPRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);

        // Farmer
        $this->app->bind(FarmerRepositoryInterface::class, EloquentFarmerRepository::class);
        $this->app->singleton(RegisterFarmerService::class);
        $this->app->singleton(UpdateFarmerProfileService::class);

        // Farm / Harvest
        $this->app->bind(HarvestRepositoryInterface::class, EloquentHarvestRepository::class);
        $this->app->singleton(RecordHarvestService::class);
        $this->app->singleton(SendHarvestToWarehouseService::class);

        // Stock
        $this->app->bind(StockRepositoryInterface::class, EloquentStockRepository::class);
        $this->app->singleton(CreateStockService::class);
        $this->app->singleton(AutoUpdateStockOnHarvestStored::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
