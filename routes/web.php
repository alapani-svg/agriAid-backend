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

// API routes have been moved to routes/api.php to use the api middleware (no CSRF).
