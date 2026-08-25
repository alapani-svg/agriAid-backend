<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Http\Resources\LocationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Location::query()->orderBy('type')->orderBy('name');

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('active')) {
            $query->where('active', (bool) $request->query('active'));
        }

        return response()->json(LocationResource::collection($query->get()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:city,region,commodity',
            'region' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $data['active'] = $data['active'] ?? true;

        return response()->json(new LocationResource(Location::create($data)), 201);
    }

    public function show(Location $location): JsonResponse
    {
        return response()->json(new LocationResource($location));
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:city,region,commodity',
            'region' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $location->update($data);

        return response()->json(new LocationResource($location));
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return response()->json(null, 204);
    }
}
