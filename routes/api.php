<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OTPController;
use App\Farmer\Presentation\Controllers\FarmerController;
use App\Farm\Presentation\Controllers\HarvestController;
use App\Stock\Presentation\Controllers\StockController;
use App\Credibility\Presentation\Controllers\CredibilityScoreController;
use App\Warehouse\Presentation\Controllers\WarehouseController;
use App\Warehouse\Presentation\Controllers\WarehouseSensorReadingController;
use App\Warehouse\Presentation\Controllers\WarehouseGateLogController;
use App\Receipt\Presentation\Controllers\WarehouseReceiptController;
use App\Notifications\Presentation\Controllers\NotificationController;
use App\Store\Presentation\Controllers\StoreController;
use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\MarketListingController;
use App\Http\Controllers\MarketPriceController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RegionalReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\PlatformNotificationController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\FarmerEstatePhotoController;
use App\Http\Controllers\SseController;
use App\Http\Controllers\WmsController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/avatar', [AuthController::class, 'updateAvatar'])->middleware('auth:sanctum');
Route::delete('/auth/avatar', [AuthController::class, 'deleteAvatar'])->middleware('auth:sanctum');
Route::put('/auth/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::put('/auth/password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
Route::get('/auth/sessions', [AuthController::class, 'sessions'])->middleware('auth:sanctum');
Route::delete('/auth/sessions/other', [AuthController::class, 'revokeOtherSessions'])->middleware('auth:sanctum');
Route::delete('/auth/sessions/{tokenId}', [AuthController::class, 'revokeSession'])->middleware('auth:sanctum');
Route::delete('/auth/account', [AuthController::class, 'destroyAccount'])->middleware('auth:sanctum');

Route::post('/otp/generate', [OTPController::class, 'generate'])->middleware('throttle:auth');
Route::post('/otp/verify', [OTPController::class, 'verify'])->middleware('throttle:auth');

/*
| Module 2 — Activity documentation & stock tracking (Developer 1)
| Farmer profile, Harvest registration, Stock tracking
*/
Route::middleware('auth:sanctum')->group(function () {
    // Farmer profile endpoints (farmers can self-register their own profile; admins can register for anyone)
    Route::post('/farmers', [FarmerController::class, 'register'])->middleware('role:farmer,admin');
    Route::get('/farmers', [FarmerController::class, 'index'])->middleware('role:admin');
    Route::get('/farmers/me', [FarmerController::class, 'me'])->middleware('role:farmer,admin');
    Route::get('/farmers/by-user/{userId}', [FarmerController::class, 'showByUserId'])->middleware('role:admin');
    Route::get('/farmers/{id}', [FarmerController::class, 'show'])->middleware('role:farmer,admin');
    Route::put('/farmers/{id}', [FarmerController::class, 'update'])->middleware('role:farmer,admin');

    // Farmer estate photos
    Route::get('/farmer-estate-photos', [FarmerEstatePhotoController::class, 'index'])->middleware('role:farmer');
    Route::post('/farmer-estate-photos', [FarmerEstatePhotoController::class, 'store'])->middleware('role:farmer');
    Route::delete('/farmer-estate-photos/{estateId}', [FarmerEstatePhotoController::class, 'destroy'])->middleware('role:farmer');

    // Harvest endpoints (farmer only)
    Route::get('/harvests', [HarvestController::class, 'index'])->middleware('role:farmer,admin,warehouse');
    Route::post('/harvests', [HarvestController::class, 'record'])->middleware('role:farmer');
    Route::get('/harvests/{id}', [HarvestController::class, 'show'])->middleware('role:farmer,admin');
    Route::post('/harvests/{id}/send-to-warehouse', [HarvestController::class, 'sendToWarehouse'])->middleware('role:farmer');
    Route::post('/harvests/{id}/store', [HarvestController::class, 'storeInWarehouse'])->middleware('role:farmer,warehouse,admin');

    // Stock endpoints (read-only; stock is auto-updated on harvest storage)
    Route::get('/stocks', [StockController::class, 'index'])->middleware('role:farmer,admin,warehouse');
    Route::get('/stocks/{id}', [StockController::class, 'show'])->middleware('role:farmer,admin,warehouse');
    Route::post('/stocks/{id}/verify-photo', [StockController::class, 'verifyPhoto'])->middleware('role:farmer,admin,warehouse');

    // Warehouse endpoints
    Route::post('/warehouses', [WarehouseController::class, 'register'])->middleware('role:farmer,admin,warehouse');
    Route::get('/warehouses', [WarehouseController::class, 'index'])->middleware('role:farmer,admin,warehouse');
    Route::get('/warehouses/me', [WarehouseController::class, 'mine'])->middleware('role:warehouse,admin');
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show'])->middleware('role:farmer,admin,warehouse');
    Route::patch('/warehouses/{id}/aeration', [WarehouseController::class, 'updateAeration'])->middleware('role:warehouse,admin');
    Route::patch('/warehouses/{id}/manager', [WarehouseController::class, 'updateManager'])->middleware('role:admin');
    Route::patch('/warehouses/{id}/farmer', [WarehouseController::class, 'updateFarmer'])->middleware('role:admin');

    // Environmental telemetry (manually logged; not physical IoT hardware)
    Route::get('/warehouses/{warehouseId}/sensor-readings', [WarehouseSensorReadingController::class, 'index'])->middleware('role:farmer,admin,warehouse');
    Route::post('/warehouses/{warehouseId}/sensor-readings', [WarehouseSensorReadingController::class, 'store'])->middleware('role:warehouse,admin');

    // Gate logistics manifest (manually logged vehicle movements)
    Route::get('/warehouses/{warehouseId}/gate-logs', [WarehouseGateLogController::class, 'index'])->middleware('role:farmer,admin,warehouse');
    Route::post('/warehouses/{warehouseId}/gate-logs', [WarehouseGateLogController::class, 'store'])->middleware('role:warehouse,admin');

    // Digital warehouse receipts (QR-coded)
    Route::get('/warehouse-receipts', [WarehouseReceiptController::class, 'index'])->middleware('role:farmer,admin,warehouse');
    Route::get('/warehouse-receipts/{id}', [WarehouseReceiptController::class, 'show'])->middleware('role:farmer,admin,warehouse');

    // Credibility score (consumed by the Loans module)
    Route::get('/admin/credibility-scores', [CredibilityScoreController::class, 'index'])->middleware('role:admin');
    Route::get('/farmers/{id}/credibility-score', [CredibilityScoreController::class, 'show'])->middleware('role:farmer,admin,lender');

    // Admin user management
    Route::get('/admin/users', [AdminUserController::class, 'index'])->middleware('role:admin');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->middleware('role:admin');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->middleware('role:admin');
    Route::put('/admin/users/{id}', [AdminUserController::class, 'update'])->middleware('role:admin');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->middleware('role:admin');

    // Admin dashboard overview
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'stats'])->middleware('role:admin');

    // Store / marketplace (buyers browse in-stock produce and place orders)
    Route::get('/store/available-stock', [StoreController::class, 'availableStock'])->middleware('role:buyer,admin,farmer');
    Route::get('/store/orders', [StoreController::class, 'myOrders'])->middleware('role:buyer,admin');
    Route::post('/store/orders', [StoreController::class, 'store'])->middleware('role:buyer,admin');
    Route::get('/store/orders/{id}', [StoreController::class, 'show'])->middleware('role:buyer,admin');
    Route::patch('/store/orders/{id}/status', [StoreController::class, 'updateStatus'])->middleware('role:admin,farmer');
    Route::get('/admin/store-orders', [StoreController::class, 'adminOrders'])->middleware('role:admin');
    Route::patch('/admin/store/stock/{id}', [StoreController::class, 'updateStockListing'])->middleware('role:admin');

    // Notification center (in-app inbox; every authenticated role)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/seen', [NotificationController::class, 'markSeen']);
    Route::post('/notifications/{id}/interacted', [NotificationController::class, 'markInteracted']);
    Route::post('/notifications/mark-all-seen', [NotificationController::class, 'markAllSeen']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/notifications', [NotificationController::class, 'store'])->middleware('role:admin');
    Route::post('/admin/notifications/broadcast', [NotificationController::class, 'broadcast'])->middleware('role:admin');

    // Module 5-7 — Financing, Marketplace & Platform Services (Ngalock)
    Route::apiResource('loan-applications', LoanApplicationController::class);
    Route::apiResource('market-listings', MarketListingController::class);
    Route::get('market-prices', [MarketPriceController::class, 'index']);
    Route::post('market-prices', [MarketPriceController::class, 'store']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::apiResource('buyers', BuyerController::class)->only(['index', 'store']);
    Route::apiResource('institutions', InstitutionController::class)->only(['index', 'store']);
    Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'store']);
    Route::apiResource('regional-reports', RegionalReportController::class)->only(['index', 'store']);
    Route::apiResource('ngalock/notifications', PlatformNotificationController::class)->only(['index', 'store']);
    Route::get('ngalock/notifications/stream', [SseController::class, 'notifications']);

    // WMS — Fresh Produce Predictive Shelf-Life Engine
    Route::get('/wms/thresholds', [WmsController::class, 'thresholds']);
    Route::get('/wms/overview', [WmsController::class, 'overview']);
    Route::get('/wms/pick-list', [WmsController::class, 'pickList']);
    Route::get('/wms/alerts', [WmsController::class, 'alerts']);
});
