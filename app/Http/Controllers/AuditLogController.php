<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::latest()->paginate(20);

        return view('admin.auditlog', compact('logs'));
    }

    public function exportCsv()
    {
        $logs = AuditLog::with('user')->latest()->get();

        $filename = 'audit-logs-'.now()->format('Y-m-d-H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Write header row
            fputcsv($file, ['Date', 'User', 'Action', 'Model', 'Model ID', 'Old Values', 'New Values', 'IP Address']);

            // Write data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at,
                    $log->user?->full_name ?? 'System',
                    $log->action,
                    $log->model_type,
                    $log->model_id,
                    $log->old_values,
                    $log->new_values,
                    $log->ip_address,
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function exportPdf()
    {
        $logs = AuditLog::with('user')->latest()->get();

        $pdf = Pdf::loadView('admin.exports.audit-logs-pdf', compact('logs'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('audit-logs.pdf');
    }
}
