<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * @param  array{title: string, summary?: list<array{label: string, value: string|int|float}>, tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}  $payload
     */
    public function downloadExcel(array $payload, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('EventHub')
            ->setTitle($payload['title']);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->sanitizeSheetTitle($payload['title'] ?: 'Report'));

        $tables = $payload['tables'] ?? [];
        $maxColumns = max(2, ...array_map(
            fn (array $table) => max(1, count($table['headers'] ?? [])),
            $tables ?: [['headers' => ['A', 'B']]]
        ));

        $row = 1;
        $sheet->setCellValue("A{$row}", $payload['title']);
        $sheet->mergeCells("A{$row}:".$this->columnLetter($maxColumns)."{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row += 2;

        if (! empty($payload['summary'])) {
            $sheet->setCellValue("A{$row}", 'Summary');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;

            foreach ($payload['summary'] as $item) {
                $sheet->setCellValue("A{$row}", $item['label']);
                $sheet->setCellValue("B{$row}", $item['value']);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }
            $row++;
        }

        foreach ($tables as $table) {
            $row = $this->writeTable($sheet, $table, $row);
            $row++;
        }

        foreach (range(1, $maxColumns) as $col) {
            $sheet->getColumnDimension($this->columnLetter($col))->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array{
     *     title: string,
     *     subtitle?: string,
     *     summary?: list<array{label: string, value: string|int|float}>,
     *     filters?: list<array{label: string, value: string|int|float}>,
     *     kpis?: list<array{label: string, value: string|int|float}>,
     *     sections?: list<array{key?: string, title: string, summary?: list<array{label: string, value: string|int|float}>, tables?: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>}>,
     *     tables: list<array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}>,
     *     charts?: list<array{title: string, image: string}>
     * }  $payload
     */
    public function downloadPdf(array $payload, string $filename, string $view = 'exports.report-pdf')
    {
        $isOrganizerExport = in_array($view, [
            'organizer.exports.report-pdf',
            'organizer.exports.dashboard-pdf',
        ], true);

        $pdf = Pdf::loadView($view, [
            'title' => $payload['title'],
            'subtitle' => $payload['subtitle'] ?? null,
            'summary' => $payload['summary'] ?? [],
            'filters' => $payload['filters'] ?? [],
            'kpis' => $payload['kpis'] ?? ($payload['summary'] ?? []),
            'sections' => $payload['sections'] ?? [],
            'tables' => $payload['tables'],
            'charts' => $payload['charts'] ?? [],
            'generatedAt' => now()->format('d M Y, H:i'),
        ])->setPaper('a4', $isOrganizerExport ? 'portrait' : 'landscape');

        return $pdf->download($filename);
    }

    /**
     * @param  list<string>  $labels
     * @param  list<int|float>  $values
     * @return list<list<string|int|float>>
     */
    public function trendRows(array $labels, array $values): array
    {
        return collect($labels)->map(fn ($label, $index) => [
            $label,
            $values[$index] ?? 0,
        ])->values()->all();
    }

    /**
     * @param  array{heading: string, headers: list<string>, rows: list<list<string|int|float|null>>}  $table
     */
    private function writeTable(Worksheet $sheet, array $table, int $row): int
    {
        $headers = $table['headers'] ?? [];
        $colCount = max(1, count($headers));

        $sheet->setCellValue("A{$row}", $table['heading'] ?? 'Data');
        $sheet->mergeCells("A{$row}:".$this->columnLetter($colCount)."{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $row++;

        foreach ($headers as $colIndex => $header) {
            $cell = $this->columnLetter($colIndex + 1).$row;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4F46E5');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $row++;

        foreach ($table['rows'] ?? [] as $dataRow) {
            foreach ($dataRow as $colIndex => $value) {
                $sheet->setCellValue($this->columnLetter($colIndex + 1).$row, $value);
            }
            $row++;
        }

        return $row;
    }

    private function sanitizeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $title) ?: 'Sheet';

        return mb_substr($title, 0, 31);
    }

    private function columnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)).$letter;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $letter;
    }
}
