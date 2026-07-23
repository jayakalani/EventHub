<?php

use App\Support\Locale;

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
