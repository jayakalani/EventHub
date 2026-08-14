<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inquiries Export</title>
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
        $statusLabel = ! empty($filters['status'])
            ? \App\Enums\SupportTicketStatusEnum::tryFrom($filters['status'])?->label()
            : null;
        $filterBits = array_filter([
            $eventName ? 'Event: '.$eventName : null,
            $statusLabel ? 'Status: '.$statusLabel : null,
            ! empty($filters['q']) ? 'Search: '.$filters['q'] : null,
            ! empty($filters['from']) ? 'From: '.$filters['from'] : null,
            ! empty($filters['to']) ? 'To: '.$filters['to'] : null,
        ]);
    @endphp

    <div class="header">
        <p class="brand">EventHub</p>
        <h1>Inquiries Export</h1>
        <p class="meta">
            Generated {{ now()->format('M j, Y \a\t H:i') }}
            · {{ number_format($inquiries->count()) }} {{ $inquiries->count() === 1 ? 'inquiry' : 'inquiries' }}
            @if ($filterBits !== [])
                · {{ implode(' · ', $filterBits) }}
            @endif
        </p>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 36px;">ID</th>
                <th>Subject</th>
                <th style="width: 130px;">Attendee</th>
                <th>Event</th>
                <th style="width: 110px;">Assignee</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 110px;">Submitted</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inquiries as $inquiry)
                <tr>
                    <td>{{ $inquiry->id }}</td>
                    <td>{{ $inquiry->subject }}</td>
                    <td>{{ $inquiry->user?->full_name ?? '—' }}</td>
                    <td>{{ $inquiry->event?->name ?? '—' }}</td>
                    <td>{{ $inquiry->assignee?->full_name ?? 'Unassigned' }}</td>
                    <td>{{ $inquiry->status->label() }}</td>
                    <td>{{ $inquiry->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">No inquiries match the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        EventHub · Confidential CRO inquiries export · {{ config('app.name', 'EventHub') }}
    </div>
</body>
</html>
