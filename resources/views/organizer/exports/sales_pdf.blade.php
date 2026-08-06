<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Export</title>
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
            width: 25%;
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
        .mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9px;
        }
        .right {
            text-align: right;
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
        $filterBits = array_filter([
            $eventName ? 'Event: '.$eventName : null,
            ! empty($filters['search']) ? 'Search: '.$filters['search'] : null,
            ! empty($filters['from_date']) ? 'From: '.$filters['from_date'] : null,
            ! empty($filters['to_date']) ? 'To: '.$filters['to_date'] : null,
        ]);
    @endphp

    <div class="header">
        <p class="brand">EventHub</p>
        <h1>Sales Export</h1>
        <p class="meta">
            Generated {{ now()->format('M j, Y \a\t H:i') }}
            · {{ number_format(count($sales)) }} {{ count($sales) === 1 ? 'purchase' : 'purchases' }}
            @if ($filterBits !== [])
                · {{ implode(' · ', $filterBits) }}
            @endif
        </p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <p class="label">Purchases</p>
                <p class="value">{{ number_format($stats['purchases']) }}</p>
            </td>
            <td>
                <p class="label">Tickets Sold</p>
                <p class="value">{{ number_format($stats['tickets']) }}</p>
            </td>
            <td>
                <p class="label">Unique Buyers</p>
                <p class="value">{{ number_format($stats['unique_buyers']) }}</p>
            </td>
            <td>
                <p class="label">Revenue</p>
                <p class="value">LKR {{ number_format($stats['revenue'], 2) }}</p>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 110px;">Purchased At</th>
                <th>Buyer</th>
                <th>Event</th>
                <th>Ticket Types</th>
                <th class="right" style="width: 50px;">Qty</th>
                <th class="right" style="width: 90px;">Amount</th>
                <th style="width: 130px;">Payment Ref</th>
                <th style="width: 70px;">Method</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $purchase)
                <tr>
                    <td>{{ $purchase['booked_at_formatted'] ?? '—' }}</td>
                    <td>
                        <strong>{{ $purchase['buyer'] ?? 'Unknown' }}</strong><br>
                        <span style="color:#64748b;">{{ $purchase['email'] ?? '—' }}</span>
                    </td>
                    <td>{{ $purchase['event'] ?? '—' }}</td>
                    <td>{{ implode(', ', $purchase['categories'] ?? ['General']) }}</td>
                    <td class="right">{{ number_format($purchase['quantity'] ?? 0) }}</td>
                    <td class="right">LKR {{ number_format((float) ($purchase['amount'] ?? 0), 2) }}</td>
                    <td class="mono">{{ $purchase['payment_reference'] ?? '—' }}</td>
                    <td>{{ ucfirst($purchase['payment_method'] ?? '—') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">No confirmed sales match the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        EventHub · Confidential organizer sales export · {{ config('app.name', 'EventHub') }}
    </div>
</body>
</html>
