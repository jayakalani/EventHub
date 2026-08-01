<?php

namespace App\Support;

class TitleCase
{
    /**
     * Convert human-readable text to Title Case.
     *
     * Leaves null/empty values unchanged. Collapses internal whitespace.
     * Handles hyphenated and apostrophe name parts (e.g. Mary-Jane, O'Brien).
     */
    public static function format(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($trimmed === '') {
            return $trimmed;
        }

        $words = explode(' ', mb_strtolower($trimmed, 'UTF-8'));

        $formatted = array_map(static function (string $word): string {
            return implode('-', array_map(
                static fn (string $part): string => self::capitalizeSegment($part),
                explode('-', $word)
            ));
        }, $words);

        return implode(' ', $formatted);
    }

    /**
     * Apply title-case formatting to selected keys in an array.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function applyTo(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if (is_string($value)) {
                $data[$field] = self::format($value);
            }
        }

        return $data;
    }

    private static function capitalizeSegment(string $segment): string
    {
        if ($segment === '') {
            return $segment;
        }

        if (str_contains($segment, "'")) {
            return implode("'", array_map(
                static function (string $part): string {
                    if ($part === '') {
                        return $part;
                    }

                    return mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8')
                        .mb_substr($part, 1, null, 'UTF-8');
                },
                explode("'", $segment)
            ));
        }

        return mb_strtoupper(mb_substr($segment, 0, 1, 'UTF-8'), 'UTF-8')
            .mb_substr($segment, 1, null, 'UTF-8');
    }
}
