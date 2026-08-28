<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFarmerController;
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
use App\Http\Controllers\FarmerExportController;
use App\Http\Controllers\ReceiptExportController;
use App\Http\Controllers\CredibilityExportController;
use App\Http\Controllers\RegionalReportExportController;
use App\Http\Controllers\FarmerAccessRequestController;
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
    Route::get('/warehouse-receipts', [WarehouseReceiptController::class, 'index'])->middleware('role:farmer,admin,warehouse,lender');
    Route::get('/warehouse-receipts/{id}', [WarehouseReceiptController::class, 'show'])->middleware('role:farmer,admin,warehouse,lender');

    // Credibility score (consumed by the Loans module)
    Route::get('/admin/credibility-scores', [CredibilityScoreController::class, 'index'])->middleware('role:admin,lender');
    Route::get('/admin/credibility-scores/export-pdf', [CredibilityExportController::class, 'exportPdf'])->middleware('role:admin');
    Route::get('/admin/credibility-scores/export-csv', [CredibilityExportController::class, 'exportCsv'])->middleware('role:admin');
    Route::get('/farmers/{id}/credibility-score', [CredibilityScoreController::class, 'show'])->middleware('role:farmer,admin,lender');

    // Farmer access requests — lender requests temporary profile access, farmer approves/denies
    Route::get('/farmer-access-requests', [FarmerAccessRequestController::class, 'index'])->middleware('role:lender,farmer,admin');
    Route::post('/farmer-access-requests', [FarmerAccessRequestController::class, 'store'])->middleware('role:lender');
    Route::get('/farmer-access-requests/{id}', [FarmerAccessRequestController::class, 'show'])->middleware('role:lender,farmer,admin');
    Route::put('/farmer-access-requests/{id}/approve', [FarmerAccessRequestController::class, 'approve'])->middleware('role:farmer');
    Route::put('/farmer-access-requests/{id}/deny', [FarmerAccessRequestController::class, 'deny'])->middleware('role:farmer');
    Route::put('/farmer-access-requests/{id}/revoke', [FarmerAccessRequestController::class, 'revoke'])->middleware('role:farmer');
    Route::get('/farmer-access-requests/check/{farmerId}', [FarmerAccessRequestController::class, 'checkAccess'])->middleware('role:lender');
    Route::get('/farmer-access-requests/{farmerId}/profile', [FarmerAccessRequestController::class, 'viewFarmerProfile'])->middleware('role:lender');

    // Admin user management
    Route::get('/admin/users', [AdminUserController::class, 'index'])->middleware('role:admin');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->middleware('role:admin');
    Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->middleware('role:admin');
    Route::put('/admin/users/{id}', [AdminUserController::class, 'update'])->middleware('role:admin');
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->middleware('role:admin');

    // Admin farmer registry management
    Route::get('/admin/farmers', [AdminFarmerController::class, 'index'])->middleware('role:admin');
    Route::get('/admin/farmers/{id}', [AdminFarmerController::class, 'show'])->middleware('role:admin');
    Route::get('/admin/farmers/{id}/export-pdf', [AdminFarmerController::class, 'exportPdf'])->middleware('role:admin');
    Route::put('/admin/farmers/{id}', [AdminFarmerController::class, 'update'])->middleware('role:admin');
    Route::delete('/admin/farmers/{id}', [AdminFarmerController::class, 'destroy'])->middleware('role:admin');
    Route::post('/admin/farmers/{id}/verify', [AdminFarmerController::class, 'verify'])->middleware('role:admin');
    Route::post('/admin/farmers/{id}/unverify', [AdminFarmerController::class, 'unverify'])->middleware('role:admin');

    // Admin dashboard overview
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'stats'])->middleware('role:admin');
    Route::get('/admin/financing-stats', [AdminDashboardController::class, 'stats'])->middleware('role:admin,lender');

    // Admin loan management — tracking, status workflow & reminders
    Route::get('/admin/loans', [LoanApplicationController::class, 'adminIndex'])->middleware('role:admin,lender');
    Route::get('/admin/loans/{id}', [LoanApplicationController::class, 'adminShow'])->middleware('role:admin,lender');
    Route::put('/admin/loans/{id}/status', [LoanApplicationController::class, 'adminUpdateStatus'])->middleware('role:admin,lender');
    Route::post('/admin/loans/{id}/reminder', [LoanApplicationController::class, 'adminAddReminder'])->middleware('role:admin,lender');

    // Store / marketplace (buyers browse in-stock produce and place orders)
    Route::get('/store/available-stock', [StoreController::class, 'availableStock'])->middleware('role:buyer,admin,farmer');
    Route::get('/store/admin-all-stock', [StoreController::class, 'adminAllStock'])->middleware('role:admin');
    Route::get('/store/my-stock', [StoreController::class, 'myStock'])->middleware('role:farmer');
    Route::post('/store/my-stock/{id}/publish', [StoreController::class, 'publishForSale'])->middleware('role:farmer');
    // Farmer store creation & management
    Route::post('/store/create', [StoreController::class, 'createStore'])->middleware('role:farmer,admin');
    Route::get('/store/my-store', [StoreController::class, 'myStore'])->middleware('role:farmer,admin');
    Route::put('/store/my-store', [StoreController::class, 'updateMyStore'])->middleware('role:farmer,admin');
    Route::get('/store/orders', [StoreController::class, 'myOrders'])->middleware('role:buyer,admin,farmer');
    Route::post('/store/orders', [StoreController::class, 'store'])->middleware('role:buyer,admin');
    Route::get('/store/orders/{id}', [StoreController::class, 'show'])->middleware('role:buyer,admin,farmer');
    Route::patch('/store/orders/{id}/status', [StoreController::class, 'updateStatus'])->middleware('role:buyer,admin,farmer');
    Route::get('/admin/store-orders', [StoreController::class, 'adminOrders'])->middleware('role:admin');
    Route::patch('/admin/store/stock/{id}', [StoreController::class, 'updateStockListing'])->middleware('role:admin');

    // Stock validation workflow (warehouse manager validates farmer stock before it appears on the store)
    Route::get('/store/pending-validation', [StoreController::class, 'pendingValidation'])->middleware('role:warehouse,admin');
    Route::get('/store/all-validation', [StoreController::class, 'allValidation'])->middleware('role:warehouse,admin');
    Route::post('/store/stock/{id}/validate', [StoreController::class, 'validateStock'])->middleware('role:warehouse,admin');

    // Notification center (in-app inbox; every authenticated role)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/seen', [NotificationController::class, 'markSeen']);
    Route::post('/notifications/{id}/interacted', [NotificationController::class, 'markInteracted']);
    Route::post('/notifications/mark-all-seen', [NotificationController::class, 'markAllSeen']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/notifications', [NotificationController::class, 'store'])->middleware('role:admin');
    Route::post('/admin/notifications/broadcast', [NotificationController::class, 'broadcast'])->middleware('role:admin');
    Route::get('/admin/notifications', [NotificationController::class, 'adminIndex'])->middleware('role:admin');
    Route::put('/admin/notifications/{id}', [NotificationController::class, 'update'])->middleware('role:admin');
    Route::delete('/admin/notifications/{id}', [NotificationController::class, 'adminDestroy'])->middleware('role:admin');
    Route::post('/admin/notifications/{id}/resend', [NotificationController::class, 'resend'])->middleware('role:admin');

    // Module 5-7 — Financing, Marketplace & Platform Services (Ngalock)
    Route::apiResource('loan-applications', LoanApplicationController::class);
    Route::apiResource('market-listings', MarketListingController::class);
    Route::get('market-prices', [MarketPriceController::class, 'index']);
    Route::post('market-prices', [MarketPriceController::class, 'store']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::apiResource('buyers', BuyerController::class)->only(['index', 'store']);
    Route::apiResource('institutions', InstitutionController::class)->except(['destroy']);
    Route::delete('/institutions/{id}', [InstitutionController::class, 'destroy'])->middleware('role:admin');
    Route::post('/institutions/{id}/simulate-loan', [InstitutionController::class, 'simulateLoan'])->middleware('role:admin');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('role:admin');
    Route::post('/audit-logs', [AuditLogController::class, 'store'])->middleware('role:admin');
    Route::delete('/audit-logs/{id}', [AuditLogController::class, 'destroy'])->middleware('role:admin');
    Route::delete('/audit-logs', [AuditLogController::class, 'clearAll'])->middleware('role:admin');
    Route::get('/audit-logs/export-csv', [AuditLogController::class, 'exportCsv'])->middleware('role:admin');
    Route::get('/audit-logs/export-pdf', [AuditLogController::class, 'exportPdf'])->middleware('role:admin');
    Route::apiResource('regional-reports', RegionalReportController::class)->only(['index', 'store']);
    Route::get('/regional-reports/export-pdf', [RegionalReportExportController::class, 'exportPdf'])->middleware('role:admin');
    Route::get('/regional-reports/export-csv', [RegionalReportExportController::class, 'exportCsv'])->middleware('role:admin');
    Route::get('/regional-reports/{id}/export-pdf', [RegionalReportExportController::class, 'exportSinglePdf'])->middleware('role:admin');
    Route::apiResource('ngalock/notifications', PlatformNotificationController::class)->only(['index', 'store']);
    Route::get('ngalock/notifications/stream', [SseController::class, 'notifications']);

    // WMS — Fresh Produce Predictive Shelf-Life Engine
    Route::get('/wms/thresholds', [WmsController::class, 'thresholds']);
    Route::get('/wms/overview', [WmsController::class, 'overview']);
    Route::get('/wms/pick-list', [WmsController::class, 'pickList']);
    Route::get('/wms/alerts', [WmsController::class, 'alerts']);
    Route::post('/wms/alerts/{alertId}/acknowledge', [WmsController::class, 'acknowledgeAlert']);
    Route::post('/wms/alerts/acknowledge-all', [WmsController::class, 'acknowledgeAll']);

    // Farmer data exports (Excel/CSV + PDF)
    Route::get('/farmer/{farmerId}/export/csv', [FarmerExportController::class, 'exportCsv']);
    Route::get('/farmer/{farmerId}/export/pdf', [FarmerExportController::class, 'exportPdf']);

    // Branded receipt PDF export
    Route::get('/receipts/{id}/pdf', [ReceiptExportController::class, 'exportPdf']);
});
