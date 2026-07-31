<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SendLoginOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function issueAndSend(User $user, string $purpose = 'login'): string
    {
        $code = (string) random_int(100000, 999999);
        $key = "otp:{$purpose}:{$user->id}";

        Cache::put($key, $code, now()->addMinutes(10));

        // Always log in local so the code is recoverable if SMTP fails
        if (app()->environment('local')) {
            Log::info('agriAid OTP issued', [
                'email' => $user->email,
                'purpose' => $purpose,
                'code' => $code,
                'expires_in' => '10 minutes',
            ]);
        }

        $this->deliver($user, $code, $purpose);

        return $code;
    }

    public function verify(User $user, string $code, string $purpose = 'login'): bool
    {
        $key = "otp:{$purpose}:{$user->id}";
        $expected = Cache::get($key);

        if (! $expected || (string) $expected !== (string) $code) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    private function deliver(User $user, string $code, string $purpose): void
    {
        try {
            $user->notify(new SendLoginOtpNotification($code, $purpose));

            Log::info('agriAid OTP email dispatched', [
                'email' => $user->email,
                'mailer' => config('mail.default'),
            ]);
        } catch (\Throwable $e) {
            Log::error('agriAid OTP email failed', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            // Fallback: plain mail attempt
            try {
                Mail::raw(
                    "Your agriAid verification code is: {$code}\n\nIt expires in 10 minutes.",
                    function ($message) use ($user) {
                        $message->to($user->email, $user->name)
                            ->subject('Your agriAid verification code');
                    }
                );
            } catch (\Throwable $e2) {
                Log::error('agriAid OTP fallback mail failed', [
                    'email' => $user->email,
                    'error' => $e2->getMessage(),
                ]);

                // Do not throw — registration/login must still proceed;
                // code remains in cache (+ local log).
            }
        }
    }
}
