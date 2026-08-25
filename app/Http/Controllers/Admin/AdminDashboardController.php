<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Harvest;
use App\Models\Notification;
use App\Models\Stock;
use App\Models\StoreOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'users' => User::count(),
            'farmers' => Farmer::count(),
            'harvests' => Harvest::count(),
            'stocks' => Stock::count(),
            'warehouses' => Warehouse::count(),
            'orders' => StoreOrder::count(),
            'notifications' => Notification::count(),
        ]);
    }
}
