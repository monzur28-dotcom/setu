<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(private readonly SmsSender $sms) {}

    public function issue(string $e164, string $purpose = 'REGISTER'): bool
    {
        $hash = User::hashMobile($e164);

        $recent = OtpCode::where('mobile_hash', $hash)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recent >= config('setu.otp.max_per_hour')) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), config('setu.otp.length'), '0', STR_PAD_LEFT);

        OtpCode::create([
            'mobile_hash' => $hash,
            'code_hash'   => Hash::make($code),
            'purpose'     => $purpose,
            'expires_at'  => now()->addSeconds(config('setu.otp.ttl')),
        ]);

        // No URL in the body — links trigger spam filters on BD networks.
        return $this->sms->send(
            $e164,
            __('sms.otp', ['code' => $code, 'brand' => config('app.name')]),
            critical: true,
        );
    }

    public function verify(string $e164, string $code, string $purpose = 'REGISTER'): bool
    {
        $hash = User::hashMobile($e164);

        $row = OtpCode::where('mobile_hash', $hash)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $row || $row->attempts >= config('setu.otp.max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $row->code_hash)) {
            $row->increment('attempts');

            return false;
        }

        $row->update(['consumed_at' => now()]);

        return true;
    }
}
