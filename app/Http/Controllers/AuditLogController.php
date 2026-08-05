<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->with('user')
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $modelTypes = AuditLog::query()
            ->select('model_type')
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type');

        $stats = [
            'matched' => $logs->total(),
            'today' => AuditLog::whereDate('created_at', today())->count(),
            'thisWeek' => AuditLog::where('created_at', '>=', now()->startOfWeek())->count(),
            'total' => AuditLog::count(),
        ];

        $hasActiveFilters = $request->filled('search')
            || $request->filled('action')
            || $request->filled('model_type')
            || $request->filled('from_date')
            || $request->filled('to_date');

        return view('admin.auditlog', compact(
            'logs',
            'actions',
            'modelTypes',
            'stats',
            'hasActiveFilters',
        ));
    }

    public function exportCsv(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->with('user')
            ->latest()
            ->get();

        $filename = 'audit-logs-'.now()->format('Y-m-d-H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Date', 'User', 'Action', 'Model', 'Model ID', 'Old Values', 'New Values', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at,
                    $log->user?->full_name ?? 'System',
                    $log->action,
                    $log->model_type,
                    $log->model_id,
                    is_array($log->old_values) ? json_encode($log->old_values) : $log->old_values,
                    is_array($log->new_values) ? json_encode($log->new_values) : $log->new_values,
                    $log->ip_address,
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function exportPdf(Request $request)
    {
        $logs = $this->filteredQuery($request)
            ->with('user')
            ->latest()
            ->get();

        $pdf = Pdf::loadView('admin.exports.audit-logs-pdf', compact('logs'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('audit-logs.pdf');
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = AuditLog::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function (Builder $q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('model_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return $query;
    }
}
