<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Refund Requests Export</title>
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
        .mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9px;
        }
        .right {
            text-align: right;
        }
        .muted {
            color: #64748b;
            font-size: 8px;
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
        $statusLabel = match ($filters['status'] ?? null) {
            'processed' => 'Processed (history)',
            null, '' => null,
            default => \App\Enums\RefundRequestStatusEnum::tryFrom($filters['status'])?->label(),
        };
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
        <h1>Refund Requests Export</h1>
        <p class="meta">
            Generated {{ now()->format('M j, Y \a\t H:i') }}
            · {{ number_format($refundRequests->count()) }} {{ $refundRequests->count() === 1 ? 'request' : 'requests' }}
            @if ($filterBits !== [])
                · {{ implode(' · ', $filterBits) }}
            @endif
        </p>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Event / Ticket</th>
                <th style="width: 140px;">Attendee</th>
                <th class="right" style="width: 90px;">Amount</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 120px;">Reviewed by</th>
                <th style="width: 90px;">Requested</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($refundRequests as $request)
                @php
                    $booking = $request->ticketBooking;
                    $status = $request->status;
                @endphp
                <tr>
                    <td>
                        {{ $booking?->event?->name ?? '—' }}
                        <div class="mono">{{ $booking?->ticket_number ?? '—' }}</div>
                    </td>
                    <td>
                        {{ $request->user?->full_name ?? '—' }}
                        <div class="muted">{{ $request->user?->email }}</div>
                    </td>
                    <td class="right">
                        Rs {{ number_format((float) $request->refund_amount, 2) }}
                        <div class="muted">{{ $request->refund_percentage }}%</div>
                    </td>
                    <td>{{ $status->label() }}</td>
                    <td>
                        @if ($status === \App\Enums\RefundRequestStatusEnum::Pending)
                            Awaiting review
                        @elseif ($request->reviewer)
                            {{ $request->reviewer->full_name }}
                            <div class="muted">{{ $request->reviewed_at?->format('d M Y, H:i') ?? '—' }}</div>
                        @elseif ($status->isProcessed())
                            System
                            <div class="muted">{{ $request->reviewed_at?->format('d M Y, H:i') ?? 'Auto-processed' }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $request->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">No refund requests match the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        EventHub · Confidential CRO refund export · {{ config('app.name', 'EventHub') }}
    </div>
</body>
</html>
