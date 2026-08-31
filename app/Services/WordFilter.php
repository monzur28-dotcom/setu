<?php

namespace App\Services;

use App\Models\BlockedWord;
use Illuminate\Support\Facades\Cache;

/**
 * The admin's pre-publication word list, applied to everything a member
 * writes in free text.
 *
 * A match does NOT reject anything. It raises the profile to the top of the
 * moderation queue with the matched words attached, and a person decides.
 * Automatic rejection would turn every false positive — and a list of
 * Bangla stems will produce them — into a silent, unappealable ban.
 *
 * Matching is accent- and case-insensitive and runs on word boundaries where
 * the script has them. Bangla has no case and its boundaries are unreliable,
 * so an entry with no Latin characters matches as a substring; that is the
 * deliberate trade, and the reason a human reads the result.
 */
class WordFilter
{
    private const CACHE_KEY = 'setu.blocked_words';
    private const CACHE_TTL = 300;

    /**
     * Every listed word that appears in the given texts.
     *
     * @return list<string>
     */
    public function match(?string ...$texts): array
    {
        $haystack = mb_strtolower(trim(implode("\n", array_filter($texts))));

        if ($haystack === '') {
            return [];
        }

        $locale = app()->getLocale();
        $hits   = [];

        foreach ($this->words() as $entry) {
            if ($entry['locale'] !== '*' && $entry['locale'] !== $locale) {
                continue;
            }

            if ($this->appears($entry['word'], $haystack)) {
                $hits[] = $entry['word'];
            }
        }

        return array_values(array_unique($hits));
    }

    public function flags(?string ...$texts): bool
    {
        return $this->match(...$texts) !== [];
    }

    /** Dropped whenever the admin edits the list. */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return list<array{word:string, locale:string}> */
    private function words(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => BlockedWord::query()
            ->get(['word', 'locale'])
            ->map(fn ($w) => ['word' => mb_strtolower($w->word), 'locale' => $w->locale])
            ->all());
    }

    private function appears(string $needle, string $haystack): bool
    {
        // Latin entries match whole words, so "ass" does not flag "class".
        if (preg_match('/[a-z]/u', $needle)) {
            return (bool) preg_match('/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/u', $haystack);
        }

        return str_contains($haystack, $needle);
    }
}
