<?php

namespace App\Services\Auth;

use App\Contracts\Marketing\SmsProviderInterface;
use App\Models\PhoneVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PhoneOtpService
{
    public function __construct(private SmsProviderInterface $sms) {}

    /** @return array{sent: bool, message: string, expires_at?: string} */
    public function sendOtp(string $phone, ?User $user = null): array
    {
        $phone = $this->normalizePhone($phone);
        $code = $this->generateCode();
        $verification = PhoneVerification::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('services.otp.ttl_minutes', 10)),
            'user_id' => $user?->id,
        ]);

        if (! $this->smsConfigured()) {
            return [
                'sent' => false,
                'message' => 'SMS provider is not connected',
                'expires_at' => $verification->expires_at?->toIso8601String(),
            ];
        }

        $result = $this->sms->send(
            $phone,
            sprintf('رمز التحقق WSA: %s', $code),
            ['purpose' => 'otp'],
        );

        return [
            'sent' => $result->success,
            'message' => $result->success ? 'OTP sent.' : ($result->errorMessage ?? 'SMS provider is not connected'),
            'expires_at' => $verification->expires_at?->toIso8601String(),
            'provider' => $result->provider,
        ];
    }

    public function verifyOtp(string $phone, string $code): PhoneVerification
    {
        $phone = $this->normalizePhone($phone);
        $verification = PhoneVerification::where('phone', $phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        abort_if($verification === null, 422, 'No active verification for this phone.');

        if ($verification->attempts >= (int) config('services.otp.max_attempts', 5)) {
            abort(422, 'Maximum verification attempts exceeded.');
        }

        $verification->increment('attempts');

        if (! Hash::check($code, $verification->code_hash)) {
            abort(422, 'Invalid verification code.');
        }

        $verification->update(['verified_at' => now()]);

        return $verification->fresh();
    }

    private function smsConfigured(): bool
    {
        $driver = strtolower((string) config('marketing.providers.sms', 'mock'));

        return $driver !== '' && $driver !== 'none';
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?? trim($phone);
    }

    private function generateCode(): string
    {
        $length = (int) config('services.otp.length', 6);

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
