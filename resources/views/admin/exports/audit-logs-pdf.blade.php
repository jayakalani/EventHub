<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Audit Logs Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 10px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .report-date {
            text-align: right;
            margin-bottom: 15px;
            color: #666;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table thead {
            background-color: #f3f4f6;
        }

        table th {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #374151;
        }

        table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            word-wrap: break-word;
        }

        table tbody tr:nth-child(odd) {
            background-color: #fafbfc;
        }

        table tbody tr:hover {
            background-color: #f3f4f6;
        }

        .small-text {
            font-size: 8px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <h1>Audit Logs Report</h1>
    <div class="report-date">Generated on {{ now()->format('Y-m-d H:i:s') }}</div>

    @if ($logs->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Model</th>
                    <th>Model ID</th>
                    <th>Old Values</th>
                    <th>New Values</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user?->full_name ?? 'System' }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->model_type }}</td>
                        <td>{{ $log->model_id }}</td>
                        <td class="small-text">
                            <pre style="margin: 0; white-space: pre-wrap;">{{ $log->old_values ?? '-' }}</pre>
                        </td>
                        <td class="small-text">
                            <pre style="margin: 0; white-space: pre-wrap;">{{ $log->new_values ?? '-' }}</pre>
                        </td>
                        <td>{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #6b7280;">No audit logs found.</p>
    @endif

    <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #9ca3af;">
        <p>This is an automatically generated report. Total records: {{ $logs->count() }}</p>
    </div>
</body>

</html>
