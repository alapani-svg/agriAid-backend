<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Services\AuditLogger;
use App\Services\ImageNameDescriptionMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageVerificationController extends Controller
{
    public function __construct(
        private readonly ImageNameDescriptionMatcher $matcher,
    ) {}

    /**
     * Standalone endpoint: upload an image and verify it matches a name/description.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'photo' => 'required|image|max:10240', // up to 10MB
        ]);

        $photoPath = $request->file('photo')->store('verification-photos', 'public');

        $result = $this->matcher->analyze(
            $photoPath,
            $data['name'],
            $data['description'] ?? '',
        );

        AuditLogger::log(
            action: 'image.verified',
            category: 'system',
            metadata: [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'photo_path' => $photoPath,
                'matches' => $result['matches'],
                'score' => $result['score'],
            ],
            auditableType: Stock::class,
        );

        return response()->json([
            'photo_url' => Storage::disk('public')->url($photoPath),
            'verification' => $result,
        ]);
    }

    /**
     * Verify an existing stock's photo against its declared crop/variety and notes.
     */
    public function verifyStock(Request $request, string $stockId): JsonResponse
    {
        $stock = Stock::find($stockId);

        if (!$stock) {
            return response()->json(['error' => 'Stock not found'], 404);
        }

        $photoPath = $stock->photo_path;

        if (!$photoPath) {
            return response()->json(['error' => 'Stock has no photo to verify'], 422);
        }

        $name = $stock->variety
            ? "{$stock->crop_type} ({$stock->variety})"
            : $stock->crop_type;
        $description = $stock->notes ?? '';

        $result = $this->matcher->analyze($photoPath, $name, $description);

        $stock->image_match_score = $result['score'];
        $stock->image_match_status = $result['matches'] ? 'matches' : ($result['score'] >= 50 ? 'uncertain' : 'mismatched');
        $stock->image_match_reasoning = $result['reasoning'];
        $stock->image_match_confidence = $result['confidence'];
        $stock->save();

        AuditLogger::log(
            action: 'stock.image_matched',
            category: 'stock',
            metadata: [
                'stock_id' => $stock->id,
                'crop_type' => $stock->crop_type,
                'variety' => $stock->variety,
                'matches' => $result['matches'],
                'score' => $result['score'],
            ],
            auditableType: Stock::class,
            auditableId: null,
        );

        return response()->json([
            'stock_id' => $stock->id,
            'photo_url' => $stock->photo_path ? Storage::disk('public')->url($stock->photo_path) : null,
            'verification' => $result,
        ]);
    }
}
