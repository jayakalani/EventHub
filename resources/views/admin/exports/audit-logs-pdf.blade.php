<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Audit Logs Report</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #374151;
            padding: 25px;
            background: #ffffff;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .title {
            font-size: 26px;
            font-weight: bold;
            color: #111827;
        }

        .subtitle {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        .report-info {
            float: right;
            text-align: right;
            margin-top: -40px;
            font-size: 10px;
            color: #6b7280;
        }

        /* Summary Cards */
        .summary {
            width: 100%;
            margin-bottom: 20px;
        }

        .summary td {
            width: 33.33%;
            padding: 12px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            padding: 12px;
        }

        .card-title {
            color: #6b7280;
            font-size: 10px;
        }

        .card-value {
            font-size: 20px;
            font-weight: bold;
            margin-top: 4px;
            color: #111827;
        }

        /* Section Title */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 10px;
        }

        /* Table */
        table.logs {
            width: 100%;
            border-collapse: collapse;
        }

        table.logs thead {
            background: #4f46e5;
            color: white;
        }

        table.logs th {
            padding: 10px;
            font-size: 10px;
            text-align: left;
            border: 1px solid #4338ca;
        }

        table.logs td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 9px;
        }

        table.logs tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .create {
            background: #dcfce7;
            color: #166534;
        }

        .update {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .default {
            background: #f3f4f6;
            color: #374151;
        }

        /* JSON Data */
        .json-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 5px;
            border-radius: 4px;
            font-size: 8px;
            white-space: pre-wrap;
            word-break: break-word;
            max-width: 250px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 9px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header">
        <div class="title">
            Audit Logs Report
        </div>

        <div class="subtitle">
            System Activity & Security Monitoring Report
        </div>

        <div class="report-info">
            <strong>Generated:</strong><br>
            {{ now()->format('d M Y h:i A') }}
            @if (!empty($truncated))
                <br><span style="color:#b45309;">Showing latest {{ $maxRows }} of {{ number_format($totalMatched) }}</span>
            @endif
        </div>
    </div>

    <!-- Summary -->
    <table class="summary">
        <tr>
            <td>
                <div class="card">
                    <div class="card-title">Total Records</div>
                    <div class="card-value">
                        {{ $logs->count() }}
                    </div>
                </div>
            </td>

            <td>
                <div class="card">
                    <div class="card-title">Report Date</div>
                    <div class="card-value" style="font-size:14px;">
                        {{ now()->format('d M Y') }}
                    </div>
                </div>
            </td>

            <td>
                <div class="card">
                    <div class="card-title">Status</div>
                    <div class="card-value" style="font-size:14px;color:#16a34a;">
                        Active
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">
        Audit Activity Records
    </div>

    @if($logs->count())

        <table class="logs">

            <thead>
                <tr>
                    <th width="10%">Date</th>
                    <th width="12%">User</th>
                    <th width="8%">Action</th>
                    <th width="12%">Model</th>
                    <th width="6%">ID</th>
                    <th width="20%">Old Values</th>
                    <th width="20%">New Values</th>
                    <th width="12%">IP Address</th>
                </tr>
            </thead>

            <tbody>

                @foreach($logs as $log)

                    @php
                        $actionClass = match(strtolower($log->action)) {
                            'create' => 'create',
                            'update' => 'update',
                            'delete' => 'delete',
                            default => 'default',
                        };
                    @endphp

                    <tr>

                        <td>
                            @if ($log->created_at)
                                {{ $log->created_at->format('d/m/Y') }}
                                <br>
                                <span style="color:#6b7280;">
                                    {{ $log->created_at->format('h:i A') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            {{ $log->user?->full_name ?? 'System' }}
                        </td>

                        <td>
                            <span class="badge {{ $actionClass }}">
                                {{ ucfirst($log->action) }}
                            </span>
                        </td>

                        <td>
                            {{ class_basename($log->model_type) }}
                        </td>

                        <td>
                            #{{ $log->model_id }}
                        </td>

                        <td>
                            <div class="json-box">
                                {{ $log->old_values_export ?? '-' }}
                            </div>
                        </td>

                        <td>
                            <div class="json-box">
                                {{ $log->new_values_export ?? '-' }}
                            </div>
                        </td>

                        <td>
                            {{ $log->ip_address ?? '-' }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <div style="
            text-align:center;
            padding:40px;
            border:1px dashed #d1d5db;
            color:#6b7280;
            margin-top:20px;
        ">
            <h3 style="margin-bottom:8px;">
                No Audit Logs Available
            </h3>

            <p>
                No system activities were recorded during the selected period.
            </p>
        </div>

    @endif

    <!-- Footer -->
    <div class="footer">
        EventHub Audit Management System |
        Automatically Generated Report |
        Total Records: {{ $logs->count() }}
    </div>

</body>

</html>