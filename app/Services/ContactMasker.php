<?php

namespace App\Services;

/**
 * Phone numbers, emails and social handles are masked in free text and in
 * messages — in BOTH directions — until an explicit two-sided contact
 * exchange has completed.
 *
 * This protects the profile owner, not the paywall: it applies identically
 * on the free tier and the most expensive one.
 */
class ContactMasker
{
    private const PATTERNS = [
        // Bangladeshi mobiles, with or without country code and separators
        'phone_bd'  => '/(?:\+?88)?[\s\-.]?0?1[3-9]\d[\s\-.]?\d{3}[\s\-.]?\d{4}/u',
        // Generic international runs of 9+ digits
        'phone_any' => '/(?<!\d)(?:\+?\d[\s\-.]?){9,15}(?!\d)/u',
        'email'     => '/[\w.+-]+@[\w-]+\.[\w.-]+/u',
        'url'       => '/\b(?:https?:\/\/|www\.)\S+/iu',
        'social'    => '/\b(?:fb|facebook|insta|instagram|whatsapp|imo|viber|telegram|snap)\b[\s:.\/]*[\w.@\/-]{3,}/iu',
        // Bengali digits, which naive filters miss entirely
        'phone_bn'  => '/[০-৯]{9,15}/u',
    ];

    /** @return array{0:string,1:bool,2:?string} [masked, wasFiltered, reason] */
    public function mask(string $text): array
    {
        $reason = null;

        foreach (self::PATTERNS as $key => $pattern) {
            $replaced = preg_replace($pattern, '[contact hidden]', $text);

            if ($replaced !== null && $replaced !== $text) {
                $reason ??= $key;
                $text = $replaced;
            }
        }

        return [$text, $reason !== null, $reason];
    }

    /**
     * Safety scanning, separate from contact masking: money requests,
     * coercion, and pressure to move off-platform. Flagged for moderation
     * with the recipient warned — the message is not silently dropped.
     */
    public function riskFlags(string $text): array
    {
        $flags = [];
        $lower = mb_strtolower($text);

        $money = ['send money', 'bkash', 'nagad', 'bank account', 'টাকা পাঠা', 'বিকাশ', 'নগদ', 'ধার'];
        foreach ($money as $needle) {
            if (str_contains($lower, $needle)) { $flags[] = 'MONEY_REQUEST'; break; }
        }

        $urgency = ['emergency', 'urgent', 'জরুরি', 'বিপদ'];
        foreach ($urgency as $needle) {
            if (str_contains($lower, $needle)) { $flags[] = 'URGENCY'; break; }
        }

        return array_unique($flags);
    }
}
