<?php

namespace App\Stock\Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Uses an AI vision model to sanity-check a stock's declared quantity
 * against a photo of the goods, so obviously dishonest declarations get
 * flagged for manual review instead of being listed for sale.
 *
 * This is a best-effort assist, not a hard gate: any failure (missing API
 * key, network error, unparsable response) degrades gracefully to
 * 'unavailable' rather than blocking stock management.
 */
class StockPhotoVerificationService
{
    /** Relative deviation between the AI estimate and the declared quantity that is still considered plausible. */
    private const TOLERANCE = 0.25;

    /**
     * @return array{ai_estimated_quantity_kg: ?float, ai_analysis_notes: ?string, verification_status: string}
     */
    public function analyze(string $storagePath, string $cropType, float $declaredQuantityKg): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return $this->result(null, 'AI photo verification is not configured (missing OPENAI_API_KEY).', 'unavailable');
        }

        try {
            $absolutePath = Storage::disk('public')->path($storagePath);
            $imageContents = @file_get_contents($absolutePath);

            if ($imageContents === false) {
                return $this->result(null, 'Could not read the uploaded photo for analysis.', 'unavailable');
            }

            $mime = Storage::disk('public')->mimeType($storagePath) ?: 'image/jpeg';
            $base64Image = base64_encode($imageContents);

            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 300,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a warehouse inspector estimating the visible quantity of goods in a storage photo. '
                                . 'Respond with strict JSON only, in the shape: '
                                . '{"estimated_quantity_kg": number, "confidence": "low"|"medium"|"high", "notes": string}.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => sprintf(
                                        'Crop type: %s. The stock ledger declares %.2f kg. Estimate the quantity of %s '
                                        . 'visible in this warehouse/storage photo, in kilograms, and briefly justify your estimate.',
                                        $cropType,
                                        $declaredQuantityKg,
                                        $cropType,
                                    ),
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => "data:{$mime};base64,{$base64Image}"],
                                ],
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('Stock photo verification: OpenAI request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->result(null, 'AI photo verification request failed (HTTP ' . $response->status() . ').', 'unavailable');
            }

            $content = $response->json('choices.0.message.content');
            $parsed = is_string($content) ? json_decode($content, true) : null;

            $estimated = isset($parsed['estimated_quantity_kg']) ? (float) $parsed['estimated_quantity_kg'] : null;
            $notes = is_array($parsed) && isset($parsed['notes']) ? (string) $parsed['notes'] : 'The model did not return analysis notes.';

            if ($estimated === null || $estimated <= 0) {
                return $this->result(null, $notes, 'unavailable');
            }

            $deviation = abs($estimated - $declaredQuantityKg) / max($declaredQuantityKg, 1.0);
            $status = $deviation <= self::TOLERANCE ? 'verified' : 'flagged';

            return $this->result($estimated, $notes, $status);
        } catch (\Throwable $e) {
            Log::error('Stock photo verification failed', ['error' => $e->getMessage()]);

            return $this->result(null, 'AI photo verification error: ' . $e->getMessage(), 'unavailable');
        }
    }

    /**
     * @return array{ai_estimated_quantity_kg: ?float, ai_analysis_notes: ?string, verification_status: string}
     */
    private function result(?float $estimatedQuantityKg, ?string $notes, string $status): array
    {
        return [
            'ai_estimated_quantity_kg' => $estimatedQuantityKg,
            'ai_analysis_notes' => $notes,
            'verification_status' => $status,
        ];
    }
}
