<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Support Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 20px; }
        .stats { margin-bottom: 24px; }
        .stats td { padding: 8px 16px; border: 1px solid #e2e8f0; }
        .stats .label { background: #f8fafc; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.data th, table.data td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        table.data th { background: #f1f5f9; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
    </style>
</head>
<body>
    <h1>EventHub Support Report</h1>
    <p class="meta">Generated {{ now()->format('d M Y, H:i') }}</p>

    <table class="stats">
        <tr>
            <td class="label">Total Inquiries</td><td>{{ $totalInquiries }}</td>
            <td class="label">Total Complaints</td><td>{{ $totalComplaints }}</td>
        </tr>
        <tr>
            <td class="label">Resolved Jobs</td><td>{{ $resolvedCount }}</td>
            <td class="label">Pending Jobs</td><td>{{ $pendingCount }}</td>
        </tr>
    </table>

    <h2>Inquiries</h2>
    <table class="data">
        <thead>
            <tr>
                <th>ID</th><th>Subject</th><th>User</th><th>Event</th><th>Status</th><th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inquiries as $inquiry)
                <tr>
                    <td>{{ $inquiry->id }}</td>
                    <td>{{ $inquiry->subject }}</td>
                    <td>{{ $inquiry->user?->full_name ?? '—' }}</td>
                    <td>{{ $inquiry->event?->name ?? 'General' }}</td>
                    <td>{{ $inquiry->status->label() }}</td>
                    <td>{{ $inquiry->created_at->format('d M Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Complaints</h2>
    <table class="data">
        <thead>
            <tr>
                <th>ID</th><th>Subject</th><th>User</th><th>Event</th><th>Status</th><th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($complaints as $complaint)
                <tr>
                    <td>{{ $complaint->id }}</td>
                    <td>{{ $complaint->subject }}</td>
                    <td>{{ $complaint->user?->full_name ?? '—' }}</td>
                    <td>{{ $complaint->event?->name ?? 'General' }}</td>
                    <td>{{ $complaint->status->label() }}</td>
                    <td>{{ $complaint->created_at->format('d M Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
