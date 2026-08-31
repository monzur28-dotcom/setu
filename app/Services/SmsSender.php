<?php

namespace App\Services;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS is the highest-reach and least private channel this product uses.
 *
 * Rules baked in here, not left to callers:
 *  - transactional only by default
 *  - the brand name in the first five words
 *  - never between 22:00 and 08:00 member-local
 *  - and for Connect: the mode is NEVER named in a text message, because a
 *    text can be read by someone else on a shared phone. Spec Appendix C.1.
 */
class SmsSender
{
    public function send(string $e164, string $message, string $mode = 'MATRIMONIAL', bool $critical = false): bool
    {
        if (! $critical && $this->isQuietHours()) {
            return false;
        }

        if ($mode === 'CONNECT' && $this->namesConnect($message)) {
            throw new \RuntimeException(
                'A Connect notification must never name the mode in an SMS. '
                .'Use a neutral body, or send by push instead.'
            );
        }

        $driver = config('services.sms.driver', 'log');

        if ($driver === 'log') {
            Log::info('[SMS] '.$e164.' :: '.$message);

            return true;
        }

        $response = Http::asForm()->post(config('services.sms.url'), [
            'api_key'   => config('services.sms.key'),
            'sender_id' => config('services.sms.sender_id'),
            'msisdn'    => $e164,
            'message'   => $message,
        ]);

        return $response->successful();
    }

    private function isQuietHours(): bool
    {
        $h = (int) now()->format('G');

        return $h >= 22 || $h < 8;
    }

    private function namesConnect(string $message): bool
    {
        return (bool) preg_match('/\b(connect|পরিচয়|match|dating)\b/iu', $message);
    }
}
