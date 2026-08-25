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
use App\Farm\Application\Services\HarvestPhotoVerificationService;
use App\Farm\Application\Services\RecordHarvestService;
use App\Farm\Application\Services\SendHarvestToWarehouseService;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Infrastructure\Persistence\EloquentStockRepository;
use App\Stock\Application\Services\CreateStockService;
use App\Stock\Application\Services\AutoUpdateStockOnHarvestStored;
use App\Credibility\Application\Services\CredibilityScoreService;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use App\Warehouse\Infrastructure\Persistence\EloquentWarehouseRepository;
use App\Warehouse\Application\Services\RegisterWarehouseService;
use App\Receipt\Domain\Repositories\WarehouseReceiptRepositoryInterface;
use App\Receipt\Infrastructure\Persistence\EloquentWarehouseReceiptRepository;
use App\Receipt\Application\Services\IssueWarehouseReceiptService;
use App\Farm\Application\Services\StoreHarvestInWarehouseService;
use App\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use App\Notifications\Infrastructure\Repositories\EloquentNotificationRepository;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Warehouse\Application\Services\WarehouseCapacityAlertService;
use App\Store\Domain\Repositories\StoreOrderRepositoryInterface;
use App\Store\Infrastructure\Persistence\EloquentStoreOrderRepository;
use App\Store\Application\Services\CreateStoreOrderService;
use App\Store\Application\Services\ListAvailableStoreStockService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->singleton(HarvestPhotoVerificationService::class);
        $this->app->singleton(RecordHarvestService::class);
        $this->app->singleton(SendHarvestToWarehouseService::class);

        // Stock
        $this->app->bind(StockRepositoryInterface::class, EloquentStockRepository::class);
        $this->app->singleton(CreateStockService::class);
        $this->app->singleton(AutoUpdateStockOnHarvestStored::class);

        // Credibility
        $this->app->singleton(CredibilityScoreService::class);

        // Warehouse
        $this->app->bind(WarehouseRepositoryInterface::class, EloquentWarehouseRepository::class);
        $this->app->singleton(RegisterWarehouseService::class);

        // Receipt
        $this->app->bind(WarehouseReceiptRepositoryInterface::class, EloquentWarehouseReceiptRepository::class);
        $this->app->singleton(IssueWarehouseReceiptService::class);

        // Farm / Warehouse orchestration
        $this->app->singleton(StoreHarvestInWarehouseService::class);

        // Notifications
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->singleton(NotificationApplicationService::class);

        // Warehouse capacity alerting
        $this->app->singleton(WarehouseCapacityAlertService::class);

        // Store / marketplace
        $this->app->bind(StoreOrderRepositoryInterface::class, EloquentStoreOrderRepository::class);
        $this->app->singleton(CreateStoreOrderService::class);
        $this->app->singleton(ListAvailableStoreStockService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // General API traffic: 60 requests/minute per authenticated user, or per IP for guests.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Sensitive auth endpoints (login, register, OTP, password reset): stricter,
        // IP-scoped limit to slow down brute-force / credential-stuffing attempts.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });
    }
}
