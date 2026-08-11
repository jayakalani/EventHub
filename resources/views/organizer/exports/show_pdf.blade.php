<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $event->name }} Details</title>
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
        .section-title {
            font-size: 12px;
            color: #0F0363;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        .details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .details td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            vertical-align: top;
            width: 50%;
        }
        .details .label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 3px;
        }
        .details .value {
            color: #0f172a;
            font-size: 11px;
            font-weight: bold;
            margin: 0;
        }
        .description {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            margin-top: 8px;
            line-height: 1.45;
            color: #334155;
        }
        .description .label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px;
            font-weight: bold;
        }
        .status {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-upcoming { background: #dbeafe; color: #1d4ed8; }
        .status-ongoing { background: #dcfce7; color: #15803d; }
        .status-completed { background: #e2e8f0; color: #475569; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th {
            background: #0F0363;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 7px 5px;
            text-align: left;
            border: 1px solid #0F0363;
        }
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 6px 5px;
            vertical-align: top;
            font-size: 9px;
        }
        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-yes { background: #dcfce7; color: #15803d; }
        .badge-no { background: #fee2e2; color: #b91c1c; }
        .swatch {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            margin-right: 4px;
        }
        .footer {
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 8px;
            text-align: center;
        }
        .empty {
            text-align: center;
            color: #64748b;
            padding: 14px;
        }
    </style>
</head>
<body>
    @php
        $status = strtolower((string) $event->status);
        $statusClass = match ($status) {
            'upcoming' => 'status-upcoming',
            'ongoing' => 'status-ongoing',
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            default => 'status-completed',
        };
    @endphp

    <div class="header">
        <p class="brand">EventHub</p>
        <h1>{{ $event->name }}</h1>
        <p class="meta">
            Event details export · Generated {{ now()->format('M j, Y \a\t H:i') }}
            · <span class="status {{ $statusClass }}">{{ ucfirst($event->status) }}</span>
        </p>
    </div>

    <p class="section-title">Event Information</p>
    <table class="details">
        <tr>
            <td>
                <p class="label">Host</p>
                <p class="value">{{ $event->host->name ?? 'N/A' }}</p>
            </td>
            <td>
                <p class="label">Artists</p>
                <p class="value">{{ $event->artists->isNotEmpty() ? $event->artists->pluck('name')->implode(', ') : 'N/A' }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="label">Category</p>
                <p class="value">{{ $event->eventCategory->name ?? 'N/A' }}</p>
            </td>
            <td>
                <p class="label">Date &amp; Time</p>
                <p class="value">{{ $event->date }} {{ $event->time }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="label">Place</p>
                <p class="value">{{ $event->place }}</p>
            </td>
            <td>
                <p class="label">Total Tickets</p>
                <p class="value">{{ number_format($event->no_of_tickets) }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="label">Contact Person</p>
                <p class="value">{{ $event->contactPerson->name ?? 'N/A' }}</p>
            </td>
            <td></td>
        </tr>
    </table>

    @if (!empty($event->description))
        <div class="description">
            <p class="label">Description</p>
            {{ $event->description }}
        </div>
    @endif

    <p class="section-title">Ticket Categories</p>
    <table class="data">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Total</th>
                <th>Available</th>
                <th>Price</th>
                <th>Color</th>
                <th>Active</th>
                <th>Booking Start</th>
                <th>Booking End</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ticketCategories as $category)
                <tr>
                    <td><strong>{{ $category->name }}</strong></td>
                    <td>{{ $category->description ?? '-' }}</td>
                    <td>{{ number_format($category->no_of_tickets) }}</td>
                    <td>{{ number_format($category->no_of_available_tickets) }}</td>
                    <td>LKR {{ number_format($category->ticket_price, 2) }}</td>
                    <td>
                        @if ($category->ticket_color)
                            <span class="swatch" style="background: {{ $category->ticket_color }};"></span>
                            {{ $category->ticket_color }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $category->is_active ? 'badge-yes' : 'badge-no' }}">
                            {{ $category->is_active ? 'Yes' : 'No' }}
                        </span>
                    </td>
                    <td>{{ $category->booking_start ?? '-' }}</td>
                    <td>{{ $category->booking_end ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No ticket categories found for this event.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        EventHub · Confidential organizer export · {{ config('app.name', 'EventHub') }}
    </div>
</body>
</html>
