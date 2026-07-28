<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hosts Export</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }
        .header {
            border-bottom: 3px solid #0F0363;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #0F0363;
            text-transform: uppercase;
            margin: 0 0 4px;
        }
        h1 {
            font-size: 20px;
            color: #0F0363;
            margin: 0 0 4px;
        }
        .meta {
            color: #64748b;
            font-size: 9px;
            margin: 0;
        }
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .summary td {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            width: 33.33%;
        }
        .summary .label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 2px;
        }
        .summary .value {
            color: #0F0363;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th {
            background: #0F0363;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 7px;
            text-align: left;
            border: 1px solid #0F0363;
        }
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 7px;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #fee2e2; color: #b91c1c; }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 8px;
            text-align: center;
        }
        .empty {
            text-align: center;
            color: #64748b;
            padding: 16px;
        }
    </style>
</head>
<body>
    @php
        $activeCount = $hosts->where('is_active', true)->count();
        $inactiveCount = $hosts->count() - $activeCount;
    @endphp

    <div class="header">
        <p class="brand">EventHub</p>
        <h1>Hosts Export</h1>
        <p class="meta">Generated {{ now()->format('M j, Y \a\t H:i') }} · {{ $hosts->count() }} {{ $hosts->count() === 1 ? 'host' : 'hosts' }}</p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <p class="label">Total Hosts</p>
                <p class="value">{{ number_format($hosts->count()) }}</p>
            </td>
            <td>
                <p class="label">Active</p>
                <p class="value">{{ number_format($activeCount) }}</p>
            </td>
            <td>
                <p class="label">Inactive</p>
                <p class="value">{{ number_format($inactiveCount) }}</p>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 36px;">ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th style="width: 80px;">Status</th>
                <th style="width: 110px;">Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($hosts as $host)
                <tr>
                    <td>{{ $host->id }}</td>
                    <td><strong>{{ $host->name }}</strong></td>
                    <td>{{ $host->email }}</td>
                    <td>{{ $host->contact_number }}</td>
                    <td>
                        <span class="badge {{ $host->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $host->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>{{ optional($host->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">No hosts available for export.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        EventHub · Confidential organizer export · {{ config('app.name', 'EventHub') }}
    </div>
</body>
</html>
