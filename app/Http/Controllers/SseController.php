<?php

namespace App\Http\Controllers;

use App\Models\MarketPrice;
use App\Models\Notification;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends Controller
{
    public function notifications(): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            $headers = [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ];

            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            set_time_limit(120);

            for ($i = 0; $i < 60; $i++) {
                $payload = $this->nextPayload();

                echo "event: message\n";
                echo "data: " . json_encode($payload) . "\n\n";

                if (function_exists('flush')) {
                    flush();
                }

                sleep(5);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);

        return $response;
    }

    private function nextPayload(): array
    {
        $price = MarketPrice::inRandomOrder()->first();
        $notification = Notification::inRandomOrder()->first();

        if ($price) {
            return [
                'type' => 'price',
                'message' => sprintf(
                    '%s price in %s: %s FCFA/kg (%s %.2f%%)',
                    $price->commodity,
                    $price->city,
                    number_format($price->price_fcfa_per_kg),
                    $price->trend === 'up' ? '▲' : '▼',
                    $price->change_percent
                ),
                'category' => 'info',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if ($notification) {
            return [
                'type' => 'notification',
                'message' => $notification->message,
                'category' => $notification->type === 'alert' ? 'alert' : 'info',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return [
            'type' => 'heartbeat',
            'message' => 'agriAid Liquid Glass node is online.',
            'category' => 'success',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
