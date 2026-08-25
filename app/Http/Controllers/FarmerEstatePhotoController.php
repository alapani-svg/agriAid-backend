<?php

namespace App\Http\Controllers;

use App\Models\FarmerEstatePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FarmerEstatePhotoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $photos = FarmerEstatePhoto::where('user_id', $request->user()->id)
            ->get()
            ->map(fn (FarmerEstatePhoto $photo) => [
                'estate_id' => $photo->estate_id,
                'photo_url' => $photo->photoUrl(),
                'updated_at' => $photo->updated_at->toIso8601String(),
            ]);

        return response()->json(['data' => $photos]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'estate_id' => ['required', 'string', 'max:255'],
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $existing = FarmerEstatePhoto::where('user_id', $request->user()->id)
            ->where('estate_id', $data['estate_id'])
            ->first();

        if ($existing && $existing->photo_path) {
            Storage::disk('public')->delete($existing->photo_path);
        }

        $path = $request->file('photo')->store('estate-photos', 'public');

        $photo = FarmerEstatePhoto::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'estate_id' => $data['estate_id'],
            ],
            ['photo_path' => $path]
        );

        return response()->json([
            'estate_id' => $photo->estate_id,
            'photo_url' => $photo->photoUrl(),
        ]);
    }

    public function destroy(Request $request, string $estateId): JsonResponse
    {
        $photo = FarmerEstatePhoto::where('user_id', $request->user()->id)
            ->where('estate_id', $estateId)
            ->first();

        if ($photo) {
            if ($photo->photo_path) {
                Storage::disk('public')->delete($photo->photo_path);
            }
            $photo->delete();
        }

        return response()->json(['message' => 'Photo removed']);
    }
}
