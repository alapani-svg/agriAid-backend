<?php

namespace App\Credibility\Presentation\Controllers;

use App\Credibility\Application\Services\CredibilityScoreService;
use App\Credibility\Domain\ValueObjects\CredibilityScore;
use App\Farmer\Domain\Exceptions\FarmerNotFoundException;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CredibilityScoreController
{
    public function __construct(
        private readonly CredibilityScoreService $credibilityScoreService,
        private readonly FarmerRepositoryInterface $farmerRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $farmers = $this->farmerRepository->findAll();

        $items = array_map(function ($farmer) {
            $base = [
                'farmer_id' => $farmer->getId(),
                'user_id' => $farmer->getUserId(),
                'farm_name' => $farmer->getFarmName(),
                'region' => $farmer->getRegion()->toString(),
                'village' => $farmer->getVillage(),
            ];

            try {
                $breakdown = $this->credibilityScoreService->getBreakdown($farmer->getId());
                $score = CredibilityScore::fromValue($breakdown['total_score']);

                return [
                    ...$base,
                    ...$score->toArray(),
                    'categories' => $breakdown['categories'],
                ];
            } catch (\Exception $e) {
                return [
                    ...$base,
                    'score' => null,
                    'tier' => null,
                    'tier_label' => 'unavailable',
                    'max_financing_term_years' => null,
                    'categories' => null,
                ];
            }
        }, $farmers);

        return response()->json(['data' => $items]);
    }

    public function show(string $farmerId): JsonResponse
    {
        try {
            $breakdown = $this->credibilityScoreService->getBreakdown($farmerId);
        } catch (FarmerNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }

        $score = CredibilityScore::fromValue($breakdown['total_score']);

        return response()->json([
            'farmer_id' => $farmerId,
            ...$score->toArray(),
            'categories' => $breakdown['categories'],
        ]);
    }
}
