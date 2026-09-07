<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Analyzes an image and compares it to a given product name and description.
 *
 * Returns whether the image visually matches the declared name/description,
 * a confidence score, and an explanation. Uses GPT-4o when an API key is
 * available; otherwise falls back to a local deterministic check.
 */
class ImageNameDescriptionMatcher
{
    private const CONFIDENCE_HIGH = 'high';
    private const CONFIDENCE_MEDIUM = 'medium';
    private const CONFIDENCE_LOW = 'low';

    /** Crop name aliases and keywords for local fallback matching. */
    private const CROP_KEYWORDS = [
        'maize' => ['maize', 'corn', 'maïs'],
        'cassava' => ['cassava', 'manioc', 'tubers'],
        'yam' => ['yam', 'igname'],
        'rice' => ['rice', 'riz'],
        'cocoa' => ['cocoa', 'cacao'],
        'coffee' => ['coffee', 'café'],
        'groundnut' => ['groundnut', 'peanut', 'arachide'],
        'beans' => ['beans', 'haricot', 'kidney beans', 'black beans'],
        'plantain' => ['plantain', 'plantain'],
        'tomato' => ['tomato', 'tomate'],
        'pepper' => ['pepper', 'piment', 'capsicum', 'chili'],
        'onion' => ['onion', 'oignon'],
        'potato' => ['potato', 'patate', 'irish potato'],
        'sorghum' => ['sorghum', 'sorgho'],
        'millet' => ['millet'],
        'soybean' => ['soybean', 'soya', 'soy'],
        'ginger' => ['ginger', 'gingembre'],
        'garlic' => ['garlic', 'ail'],
        'sesame' => ['sesame', 'sésame'],
        'cowpea' => ['cowpea', 'niebe'],
        'palm_oil' => ['palm oil', 'huile de palme'],
        'shea_nut' => ['shea', 'karité'],
        'avocado' => ['avocado', 'avocat'],
        'mango' => ['mango', 'mangue'],
        'watermelon' => ['watermelon', 'pasteque'],
        'pumpkin' => ['pumpkin', 'butternut', 'courge'],
        'carrot' => ['carrot', 'carotte'],
        'cabbage' => ['cabbage', 'chou'],
        'okra' => ['okra', 'gombo'],
        'eggplant' => ['eggplant', 'aubergine'],
        'cucumber' => ['cucumber', 'concombre'],
        'lettuce' => ['lettuce', 'laitue'],
        'pineapple' => ['pineapple', 'ananas'],
        'papaya' => ['papaya', 'papaye'],
        'cashew' => ['cashew', 'cajou'],
        'macadamia' => ['macadamia'],
        'honey' => ['honey', 'miel'],
        'dried_fish' => ['fish', 'poisson', 'catfish'],
        'palm_kernel' => ['palm kernel', 'graine de palme'],
        'sunflower' => ['sunflower', 'tournesol'],
        'wheat' => ['wheat', 'blé'],
        'barley' => ['barley', 'orge'],
        'banana' => ['banana', 'banane'],
    ];

    /**
     * Analyze an image and compare it to the declared name and description.
     *
     * @return array{matches: bool, confidence: string, score: int, reasoning: string}
     */
    public function analyze(string $storagePath, string $name, string $description): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return $this->localFallback($storagePath, $name, $description);
        }

        try {
            $absolutePath = Storage::disk('public')->path($storagePath);
            $imageContents = @file_get_contents($absolutePath);

            if ($imageContents === false) {
                return $this->result(false, self::CONFIDENCE_LOW, 0, 'Could not read the uploaded photo for analysis.');
            }

            $mime = Storage::disk('public')->mimeType($storagePath) ?: 'image/jpeg';
            $base64Image = base64_encode($imageContents);

            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.vision_model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 400,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an agricultural product verification AI. Examine the photo and compare it '
                                . 'to the product name and description provided by the farmer. '
                                . 'Respond with strict JSON only, in the shape: '
                                . '{"matches": boolean, "confidence": "low"|"medium"|"high", "score": number 0-100, "reasoning": string}. '
                                . 'Score 80-100 for clear matches, 50-79 for partial/unclear matches, 0-49 for mismatches. '
                                . 'Be strict: if the image clearly shows a different product than the declared name, set matches=false.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => sprintf(
                                        "Product name: %s\nDescription: %s\n\nDoes this image visually match the declared product? Explain briefly.",
                                        $name,
                                        $description ?: 'No description provided',
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
                Log::warning('Image-description matcher: OpenAI request failed, falling back to local algorithm', [
                    'status' => $response->status(),
                ]);

                return $this->localFallback($storagePath, $name, $description);
            }

            $content = $response->json('choices.0.message.content');
            $parsed = is_string($content) ? json_decode($content, true) : null;

            if (!is_array($parsed) || !isset($parsed['matches']) || !is_bool($parsed['matches'])) {
                return $this->localFallback($storagePath, $name, $description);
            }

            $matches = (bool) $parsed['matches'];
            $confidence = in_array($parsed['confidence'] ?? '', [self::CONFIDENCE_HIGH, self::CONFIDENCE_MEDIUM, self::CONFIDENCE_LOW], true)
                ? $parsed['confidence']
                : self::CONFIDENCE_LOW;
            $score = isset($parsed['score']) ? max(0, min(100, (int) $parsed['score'])) : 0;
            $reasoning = isset($parsed['reasoning']) ? (string) $parsed['reasoning'] : 'No reasoning provided.';

            return $this->result($matches, $confidence, $score, $reasoning);
        } catch (\Throwable $e) {
            Log::error('Image-description matcher failed, falling back to local algorithm', ['error' => $e->getMessage()]);

            return $this->localFallback($storagePath, $name, $description);
        }
    }

    /**
     * Local deterministic fallback when no AI API is available.
     *
     * @return array{matches: bool, confidence: string, score: int, reasoning: string}
     */
    private function localFallback(string $storagePath, string $name, string $description): array
    {
        $absolutePath = Storage::disk('public')->path($storagePath);

        if (!file_exists($absolutePath) || !is_readable($absolutePath)) {
            return $this->result(false, self::CONFIDENCE_LOW, 0, 'Photo file not found or unreadable.');
        }

        $imageInfo = @getimagesize($absolutePath);
        if ($imageInfo === false) {
            return $this->result(false, self::CONFIDENCE_LOW, 0, 'Uploaded file is not a valid image.');
        }

        $combined = strtolower($name . ' ' . $description);
        $score = 50;
        $reasons = [];

        // 1. Check file integrity and size
        $fileSize = filesize($absolutePath) ?: 0;
        if ($fileSize > 10000) {
            $score += 10;
            $reasons[] = 'image file is substantial';
        } else {
            $score -= 10;
            $reasons[] = 'image file is very small';
        }

        // 2. Check dimensions
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        if ($width >= 640 && $height >= 480) {
            $score += 10;
            $reasons[] = 'image resolution is adequate';
        } else {
            $score -= 5;
            $reasons[] = 'image resolution is low';
        }

        // 3. Keyword matching against declared name/description
        $matchedCrop = null;
        foreach (self::CROP_KEYWORDS as $crop => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($combined, $keyword)) {
                    $matchedCrop = $crop;
                    break 2;
                }
            }
        }

        if ($matchedCrop) {
            $score += 15;
            $reasons[] = "declared name/description contains agricultural keywords for {$matchedCrop}";
        } else {
            $score -= 5;
            $reasons[] = 'declared name/description lacks recognized agricultural keywords';
        }

        // 4. Check description has meaningful content
        $descLen = strlen(trim($description));
        if ($descLen > 30) {
            $score += 10;
            $reasons[] = 'description is detailed';
        } elseif ($descLen > 0) {
            $score += 5;
            $reasons[] = 'description is brief';
        } else {
            $reasons[] = 'no description provided';
        }

        $matches = $score >= 60;
        $confidence = $score >= 80 ? self::CONFIDENCE_HIGH : ($score >= 50 ? self::CONFIDENCE_MEDIUM : self::CONFIDENCE_LOW);

        return $this->result($matches, $confidence, max(0, min(100, $score)), 'Local fallback: ' . implode('; ', $reasons) . '.');
    }

    /**
     * @param array{matches: bool, confidence: string, score: int, reasoning: string} $result
     */
    private function result(bool $matches, string $confidence, int $score, string $reasoning): array
    {
        return [
            'matches' => $matches,
            'confidence' => $confidence,
            'score' => $score,
            'reasoning' => $reasoning,
        ];
    }
}
