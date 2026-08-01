<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ExportsReportSections
{
    /**
     * @param  list<string>  $allowed
     */
    protected function validatedSection(Request $request, array $allowed): string
    {
        $section = $request->input('section', $allowed[0]);
        abort_unless(in_array($section, $allowed, true), 404);

        return $section;
    }

    protected function exportFilename(string $prefix, string $section, string $extension): string
    {
        return sprintf('%s-%s-%s.%s', $prefix, $section, now()->format('Y-m-d-His'), $extension);
    }

    /**
     * @return list<array{title: string, image: string}>
     */
    protected function validatedChartImages(Request $request): array
    {
        $validated = $request->validate([
            'charts' => ['nullable', 'array', 'max:30'],
            'charts.*.title' => ['required', 'string', 'max:120'],
            'charts.*.image' => ['required', 'string', 'max:5000000'],
        ]);

        return collect($validated['charts'] ?? [])
            ->filter(function (array $chart) {
                $image = $chart['image'] ?? '';

                return is_string($image)
                    && preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $image) === 1;
            })
            ->map(fn (array $chart) => [
                'title' => (string) $chart['title'],
                'image' => (string) $chart['image'],
            ])
            ->values()
            ->all();
    }
}
