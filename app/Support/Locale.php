<?php

namespace App\Support;

class Locale
{
    public const DEFAULT = 'en';

    public const SESSION_KEY = 'locale';

    /** @var list<string> */
    public const SUPPORTED = ['en', 'si'];

    /** @var array<string, string> */
    public const LABELS = [
        'en' => 'English',
        'si' => 'සිංහල',
    ];

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED, true);
    }

    public static function current(): string
    {
        $locale = app()->getLocale();

        return self::isSupported($locale) ? $locale : self::DEFAULT;
    }

    public static function set(string $locale): void
    {
        if (! self::isSupported($locale)) {
            $locale = self::DEFAULT;
        }

        session([self::SESSION_KEY => $locale]);
        app()->setLocale($locale);
    }

    public static function applyFromSession(): void
    {
        $locale = session(self::SESSION_KEY, self::DEFAULT);

        if (! self::isSupported($locale)) {
            $locale = self::DEFAULT;
        }

        app()->setLocale($locale);
    }
}
