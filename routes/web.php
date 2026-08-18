<?php

use App\Http\Controllers\LoanApplicationController;
use App\Http\Controllers\MarketListingController;
use App\Http\Controllers\MarketPriceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PlatformNotificationController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SseController;
use App\Http\Controllers\RegionalReportController;
use App\Http\Controllers\WarehouseReceiptController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\TranslateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::apiResource('market-listings', MarketListingController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy',
    ]);

    Route::apiResource('warehouse-receipts', WarehouseReceiptController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy',
    ]);

    Route::apiResource('loan-applications', LoanApplicationController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy',
    ]);

    Route::get('market-prices', [MarketPriceController::class, 'index']);
    Route::post('market-prices', [MarketPriceController::class, 'store']);

    Route::apiResource('institutions', InstitutionController::class)->only([
        'index',
        'store',
    ]);

    Route::apiResource('buyers', BuyerController::class)->only([
        'index',
        'store',
    ]);

    Route::apiResource('purchase-orders', PurchaseOrderController::class)->only([
        'index',
        'store',
    ]);

    Route::apiResource('audit-logs', AuditLogController::class)->only([
        'index',
        'store',
    ]);

    Route::apiResource('notifications', PlatformNotificationController::class)->only([
        'index',
        'store',
    ]);

    Route::get('notifications/stream', [SseController::class, 'notifications']);

    Route::post('ai-chat', [AiChatController::class, 'chat']);
    Route::post('translate', [TranslateController::class, 'translate']);

    Route::apiResource('regional-reports', RegionalReportController::class)->only([
        'index',
        'store',
    ]);
});
