<?php

use App\Support\Locale;
use App\Support\TitleCase;

if (! function_exists('t')) {
    /**
     * Pick a translation string for the current locale.
     *
     * @param  array<string, string>  $translations
     */
    function t(array $translations): string
    {
        $locale = Locale::current();

        return $translations[$locale]
            ?? $translations[Locale::DEFAULT]
            ?? (string) reset($translations);
    }
}

if (! function_exists('title_case')) {
    /**
     * Title-case a human-readable string (names, venues, categories, etc.).
     */
    function title_case(?string $value): ?string
    {
        return TitleCase::format($value);
    }
}
