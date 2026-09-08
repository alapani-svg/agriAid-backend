<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\ImageNameDescriptionMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VerifyStockImages extends Command
{
    protected $signature = 'stocks:verify-images {--limit=0 : Maximum number of stocks to process (0 = all)} {--sleep=1 : Seconds to sleep between AI calls to respect rate limits}';

    protected $description = 'Run the image-to-name/description matcher on all stocks with photos.';

    public function handle(ImageNameDescriptionMatcher $matcher): int
    {
        $query = Stock::query()->orderBy('created_at');
        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $stocks = $query->get();
        $total = $stocks->count();

        if ($total === 0) {
            $this->info('No stocks found to verify.');
            return self::SUCCESS;
        }

        $this->info("Verifying images for {$total} stocks...");

        $processed = 0;
        $matches = 0;
        $mismatches = 0;
        $uncertain = 0;
        $unavailable = 0;

        $sleep = (int) $this->option('sleep');

        foreach ($stocks as $stock) {
            if (!$stock->photo_path || !Storage::disk('public')->exists($stock->photo_path)) {
                $stock->image_match_status = 'unavailable';
                $stock->image_match_reasoning = 'No photo or photo file missing.';
                $stock->image_match_score = null;
                $stock->image_match_confidence = null;
                $stock->save();
                $unavailable++;
                $this->warn("[{$stock->id}] {$stock->crop_type}: no photo");
                continue;
            }

            $name = $stock->variety
                ? "{$stock->crop_type} ({$stock->variety})"
                : $stock->crop_type;
            $description = $stock->notes ?? '';

            try {
                $result = $matcher->analyze($stock->photo_path, $name, $description);

                $stock->image_match_score = $result['score'];
                $stock->image_match_status = $result['matches'] ? 'matches' : ($result['score'] >= 50 ? 'uncertain' : 'mismatched');
                $stock->image_match_reasoning = $result['reasoning'];
                $stock->image_match_confidence = $result['confidence'];
                $stock->save();

                if ($result['matches']) {
                    $matches++;
                    $this->info("[{$stock->id}] {$name}: matches ({$result['score']} / {$result['confidence']})");
                } elseif ($result['score'] >= 50) {
                    $uncertain++;
                    $this->warn("[{$stock->id}] {$name}: uncertain ({$result['score']} / {$result['confidence']})");
                } else {
                    $mismatches++;
                    $this->error("[{$stock->id}] {$name}: mismatched ({$result['score']} / {$result['confidence']})");
                }
            } catch (\Throwable $e) {
                Log::error('Stock image verification command failed for stock', [
                    'stock_id' => $stock->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("[{$stock->id}] {$name}: error - {$e->getMessage()}");
                $stock->image_match_status = 'unavailable';
                $stock->image_match_reasoning = 'Verification error: ' . $e->getMessage();
                $stock->save();
                $unavailable++;
            }

            $processed++;

            if ($sleep > 0 && $processed < $total) {
                sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. Total: {$total}, matches: {$matches}, uncertain: {$uncertain}, mismatched: {$mismatches}, unavailable: {$unavailable}");

        return self::SUCCESS;
    }
}
