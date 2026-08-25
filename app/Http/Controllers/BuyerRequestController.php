<?php

namespace App\Http\Controllers;

use App\Models\BuyerRequest;
use App\Http\Resources\BuyerRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuyerRequestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BuyerRequestResource::collection(
            BuyerRequest::orderByDesc('created_at')->get()
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'buyer_id' => 'nullable|integer|exists:buyers,id',
            'farmer_user_id' => 'nullable|integer|exists:users,id',
            'crop' => 'required|string|max:255',
            'quantity_kg' => 'required|numeric|min:0.01',
            'location' => 'nullable|string|max:255',
            'delivery_deadline' => 'nullable|date',
            'buyer_message' => 'nullable|string',
        ]);

        return response()->json(new BuyerRequestResource(BuyerRequest::create($data + ['status' => 'PENDING'])), 201);
    }

    public function quote(BuyerRequest $buyerRequest, Request $request): JsonResponse
    {
        $data = $request->validate([
            'proposed_price_per_kg' => 'required|numeric|min:0.01',
            'farmer_message' => 'nullable|string',
        ]);

        $buyerRequest->update($data + [
            'status' => 'FARMER_QUOTED',
        ]);

        return response()->json(new BuyerRequestResource($buyerRequest));
    }

    public function buyerAccept(BuyerRequest $buyerRequest): JsonResponse
    {
        $buyerRequest->update(['status' => 'BUYER_ACCEPTED']);

        return response()->json(new BuyerRequestResource($buyerRequest));
    }

    public function buyerReject(BuyerRequest $buyerRequest, Request $request): JsonResponse
    {
        $data = $request->validate([
            'rejection_reason' => 'nullable|string',
        ]);

        $buyerRequest->update($data + [
            'status' => 'BUYER_REJECTED',
            'rejected_by' => 'buyer',
        ]);

        return response()->json(new BuyerRequestResource($buyerRequest));
    }

    public function farmerApprove(BuyerRequest $buyerRequest): JsonResponse
    {
        $buyerRequest->update(['status' => 'FARMER_APPROVED']);

        return response()->json(new BuyerRequestResource($buyerRequest));
    }

    public function farmerReject(BuyerRequest $buyerRequest, Request $request): JsonResponse
    {
        $data = $request->validate([
            'rejection_reason' => 'nullable|string',
        ]);

        $buyerRequest->update($data + [
            'status' => 'FARMER_REJECTED',
            'rejected_by' => 'farmer',
        ]);

        return response()->json(new BuyerRequestResource($buyerRequest));
    }
}
