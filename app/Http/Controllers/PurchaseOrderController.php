<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Http\Resources\PurchaseOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    private const FCFA_PER_USD = 610;

    public function index(): JsonResponse
    {
        return response()->json(PurchaseOrderResource::collection(
            PurchaseOrder::orderByDesc('created_at')->get()
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => 'nullable|string|max:255|unique:purchase_orders,order_number',
            'buyer_id' => 'nullable|integer|exists:buyers,id',
            'market_listing_id' => 'nullable|integer|exists:market_listings,id',
            'commodity' => 'required|string|max:255',
            'quantity_mt' => 'required|integer|min:1',
            'price_fcfa_per_mt' => 'required|numeric|min:1',
            'price_usd_per_mt' => 'nullable|numeric|min:0',
            'delivery_city' => 'nullable|string|max:255',
            'delivery_status' => 'nullable|string|max:255',
            'payment_status' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|in:MoMo,Orange Money,Bank Transfer,Cash on Delivery',
            'status' => 'nullable|string|in:YOUR TURN,PENDING,ACCEPTED',
        ]);

        $data['order_number'] = $data['order_number'] ?? 'PO-' . now()->format('YmdHis');
        $data['price_usd_per_mt'] = $data['price_usd_per_mt'] ?? round($data['price_fcfa_per_mt'] / self::FCFA_PER_USD, 2);
        $data['total_fcfa'] = round($data['quantity_mt'] * $data['price_fcfa_per_mt'], 2);
        $data['total_usd'] = round($data['quantity_mt'] * $data['price_usd_per_mt'], 2);

        $data['status'] = $data['status'] ?? 'PENDING';

        return response()->json(new PurchaseOrderResource(PurchaseOrder::create($data)), 201);
    }
}
