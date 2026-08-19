<?php

namespace App\Support;

class SriLankaDistricts
{
    /**
     * Official administrative districts of Sri Lanka.
     *
     * @var list<string>
     */
    public const NAMES = [
        'Colombo',
        'Gampaha',
        'Kalutara',
        'Kandy',
        'Matale',
        'Nuwara Eliya',
        'Galle',
        'Matara',
        'Hambantota',
        'Jaffna',
        'Kilinochchi',
        'Mannar',
        'Vavuniya',
        'Mullaitivu',
        'Batticaloa',
        'Ampara',
        'Trincomalee',
        'Kurunegala',
        'Puttalam',
        'Anuradhapura',
        'Polonnaruwa',
        'Badulla',
        'Monaragala',
        'Ratnapura',
        'Kegalle',
    ];

    /**
     * Official provinces of Sri Lanka.
     *
     * @var list<string>
     */
    public const PROVINCES = [
        'Western',
        'Central',
        'Southern',
        'Northern',
        'Eastern',
        'North Western',
        'North Central',
        'Uva',
        'Sabaragamuwa',
    ];

    /**
     * @var array<string, string>
     */
    private const DISTRICT_PROVINCE = [
        'Colombo' => 'Western',
        'Gampaha' => 'Western',
        'Kalutara' => 'Western',
        'Kandy' => 'Central',
        'Matale' => 'Central',
        'Nuwara Eliya' => 'Central',
        'Galle' => 'Southern',
        'Matara' => 'Southern',
        'Hambantota' => 'Southern',
        'Jaffna' => 'Northern',
        'Kilinochchi' => 'Northern',
        'Mannar' => 'Northern',
        'Vavuniya' => 'Northern',
        'Mullaitivu' => 'Northern',
        'Batticaloa' => 'Eastern',
        'Ampara' => 'Eastern',
        'Trincomalee' => 'Eastern',
        'Kurunegala' => 'North Western',
        'Puttalam' => 'North Western',
        'Anuradhapura' => 'North Central',
        'Polonnaruwa' => 'North Central',
        'Badulla' => 'Uva',
        'Monaragala' => 'Uva',
        'Ratnapura' => 'Sabaragamuwa',
        'Kegalle' => 'Sabaragamuwa',
    ];

    /**
     * Towns, suburbs, and common spellings mapped to an official district.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'nawala' => 'Colombo',
        'nugegoda' => 'Colombo',
        'rajagiriya' => 'Colombo',
        'maharagama' => 'Colombo',
        'kotte' => 'Colombo',
        'sri jayawardenepura' => 'Colombo',
        'sri jayawardenapura' => 'Colombo',
        'battaramulla' => 'Colombo',
        'kohuwala' => 'Colombo',
        'dehiwala' => 'Colombo',
        'mount lavinia' => 'Colombo',
        'moratuwa' => 'Colombo',
        'wellawatte' => 'Colombo',
        'bambalapitiya' => 'Colombo',
        'kollupitiya' => 'Colombo',
        'kolonnawa' => 'Colombo',
        'kaduwela' => 'Colombo',
        'homagama' => 'Colombo',
        'piliyandala' => 'Colombo',
        'boralesgamuwa' => 'Colombo',
        'kirulapone' => 'Colombo',
        'borella' => 'Colombo',
        'pettah' => 'Colombo',
        'negombo' => 'Gampaha',
        'kelaniya' => 'Gampaha',
        'wattala' => 'Gampaha',
        'ja-ela' => 'Gampaha',
        'ja ela' => 'Gampaha',
        'kandana' => 'Gampaha',
        'ragama' => 'Gampaha',
        'minuwangoda' => 'Gampaha',
        'katunayake' => 'Gampaha',
        'seeduwa' => 'Gampaha',
        'kiribathgoda' => 'Gampaha',
        'panadura' => 'Kalutara',
        'horana' => 'Kalutara',
        'beruwala' => 'Kalutara',
        'aluthgama' => 'Kalutara',
        'matugama' => 'Kalutara',
        'bandaragama' => 'Kalutara',
        'wadduwa' => 'Kalutara',
        'peradeniya' => 'Kandy',
        'gampola' => 'Kandy',
        'katugastota' => 'Kandy',
        'kundasale' => 'Kandy',
        'pilimathalawa' => 'Kandy',
        'dambulla' => 'Matale',
        'sigiriya' => 'Matale',
        'hatton' => 'Nuwara Eliya',
        'talawakele' => 'Nuwara Eliya',
        'nuwaraeliya' => 'Nuwara Eliya',
        'hikkaduwa' => 'Galle',
        'unawatuna' => 'Galle',
        'ambalangoda' => 'Galle',
        'weligama' => 'Matara',
        'mirissa' => 'Matara',
        'dickwella' => 'Matara',
        'tangalle' => 'Hambantota',
        'tissamaharama' => 'Hambantota',
        'hambantota town' => 'Hambantota',
        'nallur' => 'Jaffna',
        'chavakachcheri' => 'Jaffna',
        'point pedro' => 'Jaffna',
        'kalmunai' => 'Ampara',
        'akkaraipattu' => 'Ampara',
        'kinniya' => 'Trincomalee',
        'kuliyapitiya' => 'Kurunegala',
        'chilaw' => 'Puttalam',
        'wennappuwa' => 'Puttalam',
        'mihintale' => 'Anuradhapura',
        'kekirawa' => 'Anuradhapura',
        'kaduruwela' => 'Polonnaruwa',
        'bandarawela' => 'Badulla',
        'haputale' => 'Badulla',
        'ella' => 'Badulla',
        'welimada' => 'Badulla',
        'mahiyanganaya' => 'Badulla',
        'wellawaya' => 'Monaragala',
        'bibile' => 'Monaragala',
        'kataragama' => 'Monaragala',
        'embilipitiya' => 'Ratnapura',
        'balangoda' => 'Ratnapura',
        'mawanella' => 'Kegalle',
        'warakapola' => 'Kegalle',
        'rambukkana' => 'Kegalle',
    ];

    private const STREET_SUFFIX = 'road|street|st|rd|lane|ln|avenue|ave|mawatha|mw|place|pl|drive|dr|junction';

    public static function resolve(?string $address): ?string
    {
        if (! is_string($address) || trim($address) === '') {
            return null;
        }

        $normalized = self::normalize($address);
        $parts = preg_split('/[,\n]+/', $normalized) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));
        $tail = $parts !== [] ? $parts[count($parts) - 1] : $normalized;

        return self::matchPlace($tail)
            ?? self::matchPlace($normalized);
    }

    public static function provinceFor(?string $district): ?string
    {
        if (! is_string($district) || $district === '') {
            return null;
        }

        return self::DISTRICT_PROVINCE[$district] ?? null;
    }

    private static function matchPlace(string $haystack): ?string
    {
        foreach (self::placesByLength() as $place => $district) {
            if (self::containsPlace($haystack, $place)) {
                return $district;
            }
        }

        return null;
    }

    /**
     * @return array<string, string> lowercase place => official district, longest first
     */
    private static function placesByLength(): array
    {
        $places = [];

        foreach (self::NAMES as $district) {
            $places[self::normalize($district)] = $district;
        }

        foreach (self::ALIASES as $alias => $district) {
            $places[self::normalize($alias)] = $district;
        }

        uksort($places, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        return $places;
    }

    private static function containsPlace(string $haystack, string $place): bool
    {
        $pattern = '/(?<![\p{L}])'.preg_quote($place, '/').'(?![\p{L}])/iu';

        if (! preg_match($pattern, $haystack, $match, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $end = $match[0][1] + strlen($match[0][0]);
        $after = substr($haystack, $end);

        return ! preg_match('/^\s*(?:'.self::STREET_SUFFIX.')\b/iu', $after);
    }

    private static function normalize(string $value): string
    {
        $value = str_replace(["\xC2\xA0", "'"], [' ', ''], $value);
        $value = strtolower(trim($value));

        return (string) preg_replace('/\s+/', ' ', $value);
    }
}
