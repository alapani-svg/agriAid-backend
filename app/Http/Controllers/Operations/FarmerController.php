<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmerController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $farmer = $this->resolveFarmer($request, createIfMissing: true);

        return response()->json([
            'farmer' => $this->payload($farmer),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $farmer = $this->resolveFarmer($request, createIfMissing: true);

        $data = $request->validate([
            'village' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:60'],
            'farm_size_hectares' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'crop_types' => ['nullable', 'array'],
            'crop_types.*' => ['string', 'max:80'],
            'cig_group' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! isset($data['region']) && $request->user()->region) {
            $data['region'] = $request->user()->region;
        }

        $farmer->fill($data);
        $farmer->save();

        return response()->json([
            'message' => 'Farmer profile updated.',
            'farmer' => $this->payload($farmer->fresh()),
        ]);
    }

    private function resolveFarmer(Request $request, bool $createIfMissing = false): Farmer
    {
        $user = $request->user();
        $farmer = Farmer::query()->where('user_id', $user->id)->first();

        if ($farmer) {
            return $farmer;
        }

        if (! $createIfMissing) {
            abort(404, 'Farmer profile not found.');
        }

        return Farmer::create([
            'user_id' => $user->id,
            'region' => $user->region,
            'cig_group' => $user->organization,
            'crop_types' => [],
            'farm_size_hectares' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Farmer $farmer): array
    {
        return [
            'id' => $farmer->id,
            'user_id' => $farmer->user_id,
            'village' => $farmer->village,
            'region' => $farmer->region,
            'farm_size_hectares' => (float) $farmer->farm_size_hectares,
            'crop_types' => $farmer->crop_types ?? [],
            'cig_group' => $farmer->cig_group,
            'notes' => $farmer->notes,
            'updated_at' => $farmer->updated_at?->toIso8601String(),
        ];
    }
}
