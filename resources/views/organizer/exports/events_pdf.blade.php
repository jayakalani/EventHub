<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Events Export</title>
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
            width: 50%;
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
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #0F0363;
        }
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }
        .status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-upcoming { background: #dbeafe; color: #1d4ed8; }
        .status-ongoing { background: #dcfce7; color: #15803d; }
        .status-completed { background: #e2e8f0; color: #475569; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }
        .status-archived { background: #fef3c7; color: #92400e; }
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
    <div class="header">
        <p class="brand">EventHub</p>
        <h1>Events Export</h1>
        <p class="meta">Generated {{ now()->format('M j, Y \a\t H:i') }} · {{ $events->count() }} {{ $events->count() === 1 ? 'event' : 'events' }}</p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <p class="label">Total Events</p>
                <p class="value">{{ number_format($events->count()) }}</p>
            </td>
            <td>
                <p class="label">Report Type</p>
                <p class="value" style="font-size: 12px;">Organizer Events List</p>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 28px;">ID</th>
                <th>Event Name</th>
                <th>Host</th>
                <th>Artists</th>
                <th>Category</th>
                <th>Date</th>
                <th>Time</th>
                <th>Place</th>
                <th style="width: 48px;">Tickets</th>
                <th style="width: 70px;">Status</th>
                <th style="width: 88px;">Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                @php
                    $status = $event->trashed() ? 'archived' : strtolower((string) $event->status);
                    $statusClass = match ($status) {
                        'upcoming' => 'status-upcoming',
                        'ongoing' => 'status-ongoing',
                        'completed' => 'status-completed',
                        'cancelled' => 'status-cancelled',
                        'archived' => 'status-archived',
                        default => 'status-completed',
                    };
                @endphp
                <tr>
                    <td>{{ $event->id }}</td>
                    <td><strong>{{ $event->name }}</strong></td>
                    <td>{{ $event->host->name ?? 'N/A' }}</td>
                    <td>{{ $event->artists->isNotEmpty() ? $event->artists->pluck('name')->implode(', ') : 'N/A' }}</td>
                    <td>{{ $event->eventCategory->name ?? 'N/A' }}</td>
                    <td>{{ $event->date }}</td>
                    <td>{{ $event->time }}</td>
                    <td>{{ $event->place }}</td>
                    <td>{{ number_format($event->no_of_tickets) }}</td>
                    <td><span class="status {{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                    <td>{{ optional($event->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="empty">No events available for export.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        EventHub · Confidential organizer export · {{ config('app.name', 'EventHub') }}
    </div>
</body>
</html>
