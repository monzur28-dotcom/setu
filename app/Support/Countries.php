<?php

namespace App\Support;

/**
 * The one place that reads config/countries.php.
 *
 * Every screen that offers a country — registration, search, the profile
 * editor, the pricing page — comes through here, so the list, the dial codes
 * and the currency mapping can never drift apart between them.
 */
class Countries
{
    /** @return array<string, string> ISO code => English name, A–Z. */
    public static function all(): array
    {
        static $names = null;

        if ($names === null) {
            $names = array_map(fn ($row) => $row[0], config('countries.list', []));
            asort($names, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $names;
    }

    /**
     * The markets that get their own place in the UI, in the configured
     * order, followed by everywhere else alphabetically. Being unfeatured
     * costs a member nothing — they are still fully selectable.
     *
     * @return array<string, string>
     */
    public static function grouped(): array
    {
        $all      = self::all();
        $featured = [];

        foreach (config('countries.featured', []) as $code) {
            if (isset($all[$code])) {
                $featured[$code] = $all[$code];
            }
        }

        return ['featured' => $featured, 'rest' => array_diff_key($all, $featured)];
    }

    public static function name(?string $code): ?string
    {
        return $code ? (self::all()[strtoupper($code)] ?? $code) : null;
    }

    public static function exists(?string $code): bool
    {
        return $code !== null && isset(config('countries.list')[strtoupper($code)]);
    }

    public static function dialCode(?string $code): ?string
    {
        return config('countries.list.'.strtoupper((string) $code).'.1');
    }

    public static function currency(?string $code): string
    {
        return config('countries.list.'.strtoupper((string) $code).'.2', 'USD');
    }

    /**
     * Dial codes for a phone-number field: one entry per distinct code, so
     * +1 appears once rather than once for the US and again for Canada.
     *
     * @return array<string, string> dial code => the countries that share it
     */
    public static function dialCodes(): array
    {
        $codes = [];

        foreach (config('countries.list', []) as $iso => [$name, $dial, $currency]) {
            $codes[$dial][] = $name;
        }

        // Longest-established markets first within a shared code, then by the
        // numeric value, so the list reads +1, +7, +20, ... not +1, +1246.
        uksort($codes, fn ($a, $b) => (int) ltrim($a, '+') <=> (int) ltrim($b, '+') ?: strcmp($a, $b));

        return array_map(fn ($names) => implode(', ', array_slice($names, 0, 2)), $codes);
    }
}
