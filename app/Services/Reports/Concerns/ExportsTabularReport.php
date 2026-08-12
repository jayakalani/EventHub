<?php

namespace App\Services\Reports\Concerns;

use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\Response;

trait ExportsTabularReport
{
    protected function downloadCsv(string $title, array $headers, array $rows, string $filenamePrefix): Response
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $filename = $filenamePrefix.'_'.now()->format('Ymd_His').'.csv';

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function downloadPdfTable(
        string $title,
        array $headers,
        array $rows,
        string $filenamePrefix,
        array $summary = [],
    ): Response {
        /** @var ReportExportService $exportService */
        $exportService = app(ReportExportService::class);

        return $exportService->downloadPdf([
            'title' => $title,
            'summary' => $summary,
            'tables' => [[
                'heading' => 'Results ('.count($rows).')',
                'headers' => $headers,
                'rows' => $rows,
            ]],
        ], $filenamePrefix.'_'.now()->format('Ymd_His').'.pdf');
    }

    protected function userDisplayName(?object $user): string
    {
        if (! $user) {
            return '—';
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : ($user->email ?? '—');
    }
}
