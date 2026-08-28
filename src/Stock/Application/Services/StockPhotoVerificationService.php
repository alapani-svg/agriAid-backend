<?php

namespace App\Stock\Application\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Verifies a stock's declared quantity against the uploaded goods photo.
 *
 * Primary path: an AI vision model (OpenAI GPT-4o) compares the photo to the
 * declared quantity and returns an independent estimate.
 *
 * Fallback path (when no API key is configured): a deterministic local
 * algorithm that inspects the image file using EXIF metadata, Shannon
 * entropy, dimension analysis, and crop-specific plausibility rules.
 */
class StockPhotoVerificationService
{
    private const TOLERANCE = 0.25;

    /** Plausible stock quantity ranges per crop (kg) — warehouse-scale. */
    private const CROP_RANGES = [
        'maize' => ['min' => 50, 'max' => 10000, 'median' => 1500],
        'cassava' => ['min' => 100, 'max' => 15000, 'median' => 2500],
        'yam' => ['min' => 50, 'max' => 6000, 'median' => 1000],
        'rice' => ['min' => 50, 'max' => 8000, 'median' => 1200],
        'cocoa' => ['min' => 20, 'max' => 4000, 'median' => 600],
        'coffee' => ['min' => 20, 'max' => 3000, 'median' => 500],
        'groundnut' => ['min' => 30, 'max' => 4000, 'median' => 800],
        'beans' => ['min' => 30, 'max' => 5000, 'median' => 1000],
        'plantain' => ['min' => 50, 'max' => 6000, 'median' => 1200],
        'tomato' => ['min' => 20, 'max' => 3000, 'median' => 600],
        'pepper' => ['min' => 10, 'max' => 1500, 'median' => 300],
        'onion' => ['min' => 20, 'max' => 2500, 'median' => 600],
        'potato' => ['min' => 50, 'max' => 8000, 'median' => 1400],
        'sorghum' => ['min' => 50, 'max' => 6000, 'median' => 1000],
        'millet' => ['min' => 30, 'max' => 4000, 'median' => 800],
    ];

    private const DEFAULT_RANGE = ['min' => 10, 'max' => 10000, 'median' => 1000];

    /**
     * @return array{ai_estimated_quantity_kg: ?float, ai_analysis_notes: ?string, verification_status: string}
     */
    public function analyze(string $storagePath, string $cropType, float $declaredQuantityKg): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return $this->localAlgorithm($storagePath, $cropType, $declaredQuantityKg);
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
                Log::warning('Stock photo verification: OpenAI request failed, falling back to local algorithm', [
                    'status' => $response->status(),
                ]);

                return $this->localAlgorithm($storagePath, $cropType, $declaredQuantityKg);
            }

            $content = $response->json('choices.0.message.content');
            $parsed = is_string($content) ? json_decode($content, true) : null;

            $estimated = isset($parsed['estimated_quantity_kg']) ? (float) $parsed['estimated_quantity_kg'] : null;
            $notes = is_array($parsed) && isset($parsed['notes']) ? (string) $parsed['notes'] : 'The model did not return analysis notes.';

            if ($estimated === null || $estimated <= 0) {
                return $this->localAlgorithm($storagePath, $cropType, $declaredQuantityKg);
            }

            $deviation = abs($estimated - $declaredQuantityKg) / max($declaredQuantityKg, 1.0);
            $status = $deviation <= self::TOLERANCE ? 'verified' : 'flagged';

            return $this->result($estimated, $notes, $status);
        } catch (\Throwable $e) {
            Log::error('Stock photo verification failed, falling back to local algorithm', ['error' => $e->getMessage()]);

            return $this->localAlgorithm($storagePath, $cropType, $declaredQuantityKg);
        }
    }

    /**
     * Local deterministic verification algorithm — no external API required.
     *
     * Scoring system (0–100 points):
     *   1. File integrity      — valid image, readable          (pass/fail)
     *   2. Shannon entropy     — image complexity / realness     (0–20 pts)
     *   3. EXIF camera proof   — taken with a real camera/phone  (0–20 pts)
     *   4. EXIF date match     — photo date vs current date       (0–15 pts)
     *   5. EXIF manipulation   — no editing software detected    (0–10 pts)
     *   6. Image dimensions    — adequate resolution             (0–10 pts)
     *   7. File size           — adequate detail                  (0–10 pts)
     *   8. Quantity plausibility — within crop-specific range     (0–15 pts)
     *
     * Score >= 70 → verified
     * Score 40–69 → pending (manual review)
     * Score < 40  → flagged
     *
     * @return array{ai_estimated_quantity_kg: ?float, ai_analysis_notes: ?string, verification_status: string}
     */
    private function localAlgorithm(string $storagePath, string $cropType, float $declaredQuantityKg): array
    {
        $absolutePath = Storage::disk('public')->path($storagePath);

        // ── 1. File integrity ──────────────────────────────────────
        if (!file_exists($absolutePath)) {
            return $this->result(null, 'Photo file not found on disk.', 'unavailable');
        }

        $imageInfo = @getimagesize($absolutePath);
        if ($imageInfo === false) {
            return $this->result(null, 'The uploaded file is not a valid image.', 'unavailable');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mime = $imageInfo['mime'] ?? 'image/jpeg';
        $fileSize = filesize($absolutePath) ?: 0;
        $fileHash = hash_file('sha256', $absolutePath) ?: 'unknown';

        $checks = [];
        $score = 0;

        // ── 2. Shannon entropy ─────────────────────────────────────
        $entropy = $this->shannonEntropy($absolutePath);
        $entropyScore = 0;
        if ($entropy >= 7.5) {
            $entropyScore = 20;
            $checks[] = sprintf('entropy %.2f (high detail — real photo)', $entropy);
        } elseif ($entropy >= 7.0) {
            $entropyScore = 15;
            $checks[] = sprintf('entropy %.2f (good detail)', $entropy);
        } elseif ($entropy >= 6.0) {
            $entropyScore = 8;
            $checks[] = sprintf('entropy %.2f (moderate detail)', $entropy);
        } elseif ($entropy >= 4.0) {
            $entropyScore = 3;
            $checks[] = sprintf('entropy %.2f (low detail — suspicious)', $entropy);
        } else {
            $entropyScore = 0;
            $checks[] = sprintf('entropy %.2f (very low — likely not a real photo)', $entropy);
        }
        $score += $entropyScore;

        // ── 3. EXIF camera proof ───────────────────────────────────
        $exif = $this->safeReadExif($absolutePath, $mime);
        $cameraScore = 0;
        $cameraInfo = 'no camera metadata';

        if ($exif !== null) {
            $make = $exif['Make'] ?? null;
            $model = $exif['Model'] ?? null;

            if ($make || $model) {
                $cameraScore = 20;
                $cameraInfo = trim(($make ?? '') . ' ' . ($model ?? ''));
                $checks[] = "camera: {$cameraInfo}";
            } elseif (isset($exif['DateTimeOriginal']) || isset($exif['DateTimeDigitized'])) {
                $cameraScore = 10;
                $checks[] = 'no camera make but has timestamp';
            }
        }
        $score += $cameraScore;

        // ── 4. EXIF date match ─────────────────────────────────────
        $dateScore = 0;
        $photoDate = $exif['DateTimeOriginal'] ?? $exif['DateTimeDigitized'] ?? null;

        if ($photoDate !== null) {
            try {
                $photoTimestamp = strtotime($photoDate);
                $nowTimestamp = time();
                $dateDiffDays = abs($photoTimestamp - $nowTimestamp) / 86400;

                if ($dateDiffDays <= 7) {
                    $dateScore = 15;
                    $checks[] = sprintf('photo taken recently (%s)', $photoDate);
                } elseif ($dateDiffDays <= 30) {
                    $dateScore = 10;
                    $checks[] = sprintf('photo taken %d days ago', (int) $dateDiffDays);
                } elseif ($dateDiffDays <= 90) {
                    $dateScore = 5;
                    $checks[] = sprintf('photo taken %d days ago', (int) $dateDiffDays);
                } else {
                    $dateScore = 0;
                    $checks[] = sprintf('photo is old (%d days) — may not reflect current stock', (int) $dateDiffDays);
                }
            } catch (\Throwable $e) {
                $dateScore = 0;
            }
        } else {
            $checks[] = 'no EXIF date — cannot verify recency';
        }
        $score += $dateScore;

        // ── 5. EXIF manipulation check ────────────────────────────
        $manipulationScore = 10;
        if ($exif !== null) {
            $software = $exif['Software'] ?? $exif['ProcessingSoftware'] ?? null;
            if ($software !== null) {
                $softwareLower = strtolower($software);
                $editingApps = ['photoshop', 'gimp', 'lightroom', 'snapseed', 'pixlr', 'canva', 'affinity'];
                foreach ($editingApps as $app) {
                    if (str_contains($softwareLower, $app)) {
                        $manipulationScore = 0;
                        $checks[] = "edited with {$software} — flagged";
                        break;
                    }
                }
                if ($manipulationScore === 10) {
                    $checks[] = "software: {$software}";
                }
            }
        }
        $score += $manipulationScore;

        // ── 6. Image dimensions ───────────────────────────────────
        $dimScore = 0;
        $minDim = min($width, $height);
        $megapixels = ($width * $height) / 1_000_000;

        if ($minDim >= 720 && $megapixels >= 0.5) {
            $dimScore = 10;
            $checks[] = sprintf('resolution %dx%d (%.1f MP — high)', $width, $height, $megapixels);
        } elseif ($minDim >= 480 && $megapixels >= 0.2) {
            $dimScore = 7;
            $checks[] = sprintf('resolution %dx%d (%.1f MP — adequate)', $width, $height, $megapixels);
        } elseif ($minDim >= 200) {
            $dimScore = 3;
            $checks[] = sprintf('resolution %dx%d (low)', $width, $height);
        } else {
            $dimScore = 0;
            $checks[] = sprintf('resolution %dx%d (too low — flagged)', $width, $height);
        }
        $score += $dimScore;

        // ── 7. File size ───────────────────────────────────────────
        $sizeScore = 0;
        $sizeKb = $fileSize / 1024;

        if ($sizeKb >= 200) {
            $sizeScore = 10;
            $checks[] = sprintf('file size %.0f KB (detailed)', $sizeKb);
        } elseif ($sizeKb >= 80) {
            $sizeScore = 7;
            $checks[] = sprintf('file size %.0f KB (adequate)', $sizeKb);
        } elseif ($sizeKb >= 30) {
            $sizeScore = 4;
            $checks[] = sprintf('file size %.0f KB (small)', $sizeKb);
        } elseif ($sizeKb >= 10) {
            $sizeScore = 1;
            $checks[] = sprintf('file size %.0f KB (very small)', $sizeKb);
        } else {
            $sizeScore = 0;
            $checks[] = sprintf('file size %.0f KB (too small — flagged)', $sizeKb);
        }
        $score += $sizeScore;

        // ── 8. Quantity plausibility ───────────────────────────────
        $range = self::CROP_RANGES[$cropType] ?? self::DEFAULT_RANGE;
        $qtyScore = 0;

        if ($declaredQuantityKg < $range['min']) {
            $qtyScore = 0;
            $checks[] = sprintf('quantity %.1f kg below typical min (%d kg) for %s', $declaredQuantityKg, $range['min'], $cropType);
        } elseif ($declaredQuantityKg > $range['max']) {
            $qtyScore = 0;
            $checks[] = sprintf('quantity %.1f kg above typical max (%d kg) for %s', $declaredQuantityKg, $range['max'], $cropType);
        } else {
            $medianDist = abs($declaredQuantityKg - $range['median']) / max($range['median'], 1);
            if ($medianDist <= 1.0) {
                $qtyScore = 15;
                $checks[] = sprintf('quantity %.1f kg plausible for %s (near median %d kg)', $declaredQuantityKg, $cropType, $range['median']);
            } elseif ($medianDist <= 2.0) {
                $qtyScore = 10;
                $checks[] = sprintf('quantity %.1f kg within range for %s', $declaredQuantityKg, $cropType);
            } else {
                $qtyScore = 7;
                $checks[] = sprintf('quantity %.1f kg at edge of range for %s', $declaredQuantityKg, $cropType);
            }
        }
        $score += $qtyScore;

        // ── Final verdict ──────────────────────────────────────────
        $status = match (true) {
            $score >= 70 => 'verified',
            $score >= 40 => 'pending',
            default => 'flagged',
        };

        $estimatedKg = $this->estimateQuantity($score, $declaredQuantityKg, $range);

        $notes = sprintf(
            'Local algorithm: score %d/100 → %s. File: %s, %dx%d, %.0f KB. %s. Checks: %s.',
            $score,
            $status,
            substr($fileHash, 0, 12),
            $width,
            $height,
            $sizeKb,
            $cameraInfo,
            implode('; ', $checks),
        );

        return $this->result($estimatedKg, $notes, $status);
    }

    /**
     * Calculate Shannon entropy of a file's byte distribution.
     * Real photos: 7.0–8.0 bits/byte. Solid/blank images: < 4.0.
     */
    private function shannonEntropy(string $filePath): float
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return 0.0;
        }

        $data = fread($handle, 65536);
        fclose($handle);

        if ($data === false || strlen($data) === 0) {
            return 0.0;
        }

        $freq = array_fill(0, 256, 0);
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $freq[ord($data[$i])]++;
        }

        $entropy = 0.0;
        foreach ($freq as $count) {
            if ($count === 0) continue;
            $p = $count / $len;
            $entropy -= $p * log($p, 2);
        }

        return round($entropy, 4);
    }

    /**
     * Safely read EXIF data from an image file.
     */
    private function safeReadExif(string $filePath, string $mime): ?array
    {
        if (!in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
            return null;
        }

        $exif = @exif_read_data($filePath, 'ANY_TAG', true);

        if ($exif === false || empty($exif)) {
            return null;
        }

        $flat = [];
        foreach ($exif as $section => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_string($key) && !is_array($value)) {
                        $flat[$key] = $value;
                    }
                }
            } elseif (is_string($section) && !is_array($data)) {
                $flat[$section] = $data;
            }
        }

        return $flat;
    }

    /**
     * Estimate the quantity based on the verification score and crop range.
     */
    private function estimateQuantity(int $score, float $declaredKg, array $range): float
    {
        $trustFactor = match (true) {
            $score >= 80 => 1.0,
            $score >= 70 => 0.95,
            $score >= 50 => 0.80,
            $score >= 40 => 0.65,
            default => 0.40,
        };

        $estimated = ($declaredKg * $trustFactor) + ($range['median'] * (1 - $trustFactor));

        return round(max($range['min'], min($range['max'], $estimated)), 2);
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
