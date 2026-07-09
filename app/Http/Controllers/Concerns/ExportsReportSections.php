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
        $section = $request->query('section', $allowed[0]);
        abort_unless(in_array($section, $allowed, true), 404);

        return $section;
    }

    protected function exportFilename(string $prefix, string $section, string $extension): string
    {
        return sprintf('%s-%s-%s.%s', $prefix, $section, now()->format('Y-m-d-His'), $extension);
    }
}
