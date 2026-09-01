<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Turns the administrator's appearance settings into a font request and a
 * block of custom properties, emitted in the page head after app.css.
 *
 * Nothing here interpolates user input. The font stacks come from
 * config/themes.php by key, and the colours are re-formatted from a
 * validated hex match — never passed through from the request. A settings
 * screen that writes into a stylesheet is a stylesheet injection waiting to
 * happen otherwise.
 */
class Theme
{
    /** The brand tokens are derived from one colour, per theme. */
    public static function brand(): string
    {
        return self::hex(SiteSetting::get('brand_color'), '#8E1B2E');
    }

    public static function gold(): string
    {
        return self::hex(SiteSetting::get('gold_color'), '#A87C22');
    }

    public static function pairKey(): string
    {
        $key = (string) SiteSetting::get('font_pair', 'newsreader');

        return isset(config('themes.pairs')[$key]) ? $key : 'newsreader';
    }

    public static function pair(): array
    {
        return config('themes.pairs.'.self::pairKey());
    }

    /** The Google Fonts URL for the chosen pairing, plus what is always needed. */
    public static function fontUrl(): string
    {
        return 'https://fonts.googleapis.com/css2?'
            .self::pair()['google']
            .'&'.config('themes.bengali_and_mono')
            .'&display=swap';
    }

    /**
     * Every pairing at once. Only the appearance screen loads this: its
     * preview has to render in a face the administrator has not chosen yet,
     * and a preview drawn in the current font would be showing the wrong
     * thing. No other page pays for it.
     */
    public static function allFontsUrl(): string
    {
        $specs = array_map(fn ($p) => $p['google'], config('themes.pairs'));

        return 'https://fonts.googleapis.com/css2?'.implode('&', $specs)
            .'&'.config('themes.bengali_and_mono').'&display=swap';
    }

    /**
     * The override block. Only tokens an administrator actually set are
     * emitted, so anything untouched keeps the stylesheet's own value and
     * the theme system underneath goes on working.
     */
    public static function css(): string
    {
        $pair  = self::pair();
        $brand = self::brand();
        $gold  = self::gold();

        $headWeight = SiteSetting::number('heading_weight');
        $bodyWeight = SiteSetting::number('body_weight');
        $size       = SiteSetting::number('base_font_px');

        $root = [
            '--font-head' => $pair['head'],
            '--font-body' => $pair['body'],
            '--font-size' => $size.'px',
            '--w-head'    => $headWeight,
            '--w-body'    => $bodyWeight,
            '--brand'     => $brand,
            '--brand-deep' => "color-mix(in srgb, {$brand} 76%, black)",
            '--brand-tint' => "color-mix(in srgb, {$brand} 13%, white)",
            '--gold'       => $gold,
        ];

        /*
         | Dark mode needs a LIGHTER brand, not the same one. A deep red that
         | reads well on paper is close to invisible on a near-black surface,
         | so the dark branches lift it toward white rather than reuse it.
         | Without this, choosing a dark brand colour would quietly wreck
         | contrast for every viewer on a dark system theme.
         */
        $darkBrand = "color-mix(in srgb, {$brand} 52%, white)";
        $darkGold  = "color-mix(in srgb, {$gold} 62%, white)";

        $dark = [
            '--brand'      => $darkBrand,
            '--brand-deep' => "color-mix(in srgb, {$brand} 68%, white)",
            '--brand-tint' => "color-mix(in srgb, {$brand} 30%, black)",
            '--gold'       => $darkGold,
        ];

        $out  = ':root{'.self::declarations($root).'}';
        $out .= '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){'.self::declarations($dark).'}}';
        $out .= ':root[data-theme="dark"]{'.self::declarations($dark).'}';

        /*
         | Connect keeps its own palette, and needs no help from here to do
         | it. `:root[data-mode="connect"]` outranks a bare `:root` on
         | specificity, and `.half-dating` sets --brand on its own element,
         | which its descendants inherit ahead of anything on the root. An
         | earlier draft emitted `--brand:initial` for both; that would have
         | made the property invalid rather than restoring anything.
         */

        return $out;
    }

    /** @param array<string,string|int> $vars */
    private static function declarations(array $vars): string
    {
        $out = '';

        foreach ($vars as $name => $value) {
            $out .= $name.':'.$value.';';
        }

        return $out;
    }

    /**
     * A six-digit hex colour, rebuilt from the match rather than passed
     * through. Anything else falls back to the default.
     */
    private static function hex(?string $value, string $fallback): string
    {
        if (is_string($value) && preg_match('/^#?([0-9a-fA-F]{6})$/', trim($value), $m)) {
            return '#'.strtolower($m[1]);
        }

        return $fallback;
    }
}
