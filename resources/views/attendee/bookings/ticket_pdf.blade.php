<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $booking->ticket_number }}</title>

    @php
        $ticketColor = $booking->ticketCategory->ticket_color ?? '#4f46e5';
        $coverPath = ! empty($booking->event->cover)
            ? public_path('uploads/covers/events/' . $booking->event->cover)
            : null;
        $croContact = $booking->event->contactPerson->contact_number ?? null;
        $croName = $booking->event->contactPerson->full_name ?? null;
        $eventDate = \Carbon\Carbon::parse($booking->event->date)->format('D, M j, Y');
        $isEventCompleted = $booking->event->isCompleted();
        $entryBadge = $isEventCompleted ? 'Completed' : 'Confirmed';
        $statusPill = $isEventCompleted ? 'Event Completed' : 'Valid Entry Pass';
    @endphp

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            background: #eef2f7;
            color: #0f172a;
            padding: 18px 22px;
        }

        .ticket-shell {
            width: 100%;
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #dbe3ee;
        }

        .ticket-header {
            background: {{ $ticketColor }};
            color: #ffffff;
            padding: 16px 24px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-brand {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            opacity: 0.92;
        }

        .header-title {
            font-size: 13px;
            font-weight: 700;
            margin-top: 2px;
        }

        .header-meta {
            text-align: right;
            vertical-align: middle;
        }

        .status-pill {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .category-pill {
            display: inline-block;
            margin-top: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            background: #ffffff;
            color: {{ $ticketColor }};
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .ticket-body {
            padding: 22px 24px 18px;
        }

        .body-table {
            width: 100%;
            border-collapse: collapse;
        }

        .image-column {
            width: 32%;
            vertical-align: top;
            padding-right: 20px;
        }

        .details-column {
            width: 46%;
            vertical-align: top;
            padding-right: 16px;
        }

        .qr-column {
            width: 22%;
            vertical-align: middle;
            text-align: center;
        }

        .image-frame {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .event-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        .image-placeholder {
            width: 100%;
            height: 250px;
            text-align: center;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        }

        .placeholder-label {
            padding-top: 108px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .event-name {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.15;
            color: #0f172a;
            margin-bottom: 16px;
        }

        .accent-line {
            width: 56px;
            height: 4px;
            border-radius: 999px;
            background: {{ $ticketColor }};
            margin-bottom: 18px;
        }

        .details-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .details-grid td {
            padding: 7px 0;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }

        .details-grid tr:last-child td {
            border-bottom: none;
        }

        .field-label {
            width: 34%;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #64748b;
        }

        .field-value {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.45;
        }

        .field-value-muted {
            font-size: 11px;
            font-weight: 400;
            color: #475569;
            line-height: 1.5;
        }

        .ticket-number-box {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .ticket-number-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .ticket-number-value {
            font-size: 15px;
            font-weight: 700;
            color: {{ $ticketColor }};
            letter-spacing: 0.04em;
        }

        .qr-panel {
            display: inline-block;
            padding: 14px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }

        .qr-image {
            width: 148px;
            height: 148px;
        }

        .ticket-qr-code {
            display: inline-block;
            font-size: 0;
            line-height: 0;
        }

        .ticket-qr-code div {
            height: 4px;
            line-height: 0;
        }

        .ticket-qr-code span {
            display: inline-block;
            width: 4px;
            height: 4px;
        }

        .scan-label {
            margin-top: 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .scan-subtext {
            margin-top: 3px;
            font-size: 9px;
            color: #94a3b8;
            line-height: 1.4;
        }

        .confirmed-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 7px 14px;
            border-radius: 999px;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #15803d;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .ticket-footer {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 12px 24px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-left {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
        }

        .footer-right {
            text-align: right;
            font-size: 9px;
            color: #64748b;
            line-height: 1.5;
        }

        .divider-dot {
            color: #cbd5e1;
        }
    </style>
</head>

<body>

<div class="ticket-shell">

    <div class="ticket-header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-brand">{{ config('app.name') }}</div>
                    <div class="header-title">Official Event Ticket</div>
                </td>
                <td class="header-meta">
                    <div class="status-pill">{{ $statusPill }}</div>
                    <div class="category-pill">{{ $booking->ticketCategory->name }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="ticket-body">
        <table class="body-table">
            <tr>
                <td class="image-column">
                    <div class="image-frame">
                        @if ($coverPath && file_exists($coverPath))
                            <img
                                src="{{ $coverPath }}"
                                class="event-image"
                                alt="{{ $booking->event->name }}"
                            >
                        @else
                            <div class="image-placeholder">
                                <div class="placeholder-label">Event Cover</div>
                            </div>
                        @endif
                    </div>
                </td>

                <td class="details-column">
                    <div class="event-name">{{ $booking->event->name }}</div>
                    <div class="accent-line"></div>

                    <table class="details-grid">
                        <tr>
                            <td class="field-label">Venue</td>
                            <td class="field-value">{{ $booking->event->place }}</td>
                        </tr>

                        @if (! empty($booking->event->description))
                            <tr>
                                <td class="field-label">About</td>
                                <td class="field-value-muted">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($booking->event->description), 160) }}
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td class="field-label">Date &amp; Time</td>
                            <td class="field-value">
                                {{ $eventDate }}
                                @if ($booking->event->time)
                                    <span class="divider-dot"> • </span>{{ $booking->event->time }}
                                @endif
                            </td>
                        </tr>

                        

                        @if ($croName || $croContact)
                            <tr>
                                <td class="field-label">CRO Contact</td>
                                <td class="field-value">
                                    @if ($croName)
                                        {{ $croName }}
                                    @endif
                                    @if ($croName && $croContact)
                                        <span class="divider-dot"> • </span>
                                    @endif
                                    @if ($croContact)
                                        {{ $croContact }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>

                    <div class="ticket-number-box">
                        <div class="ticket-number-label">Ticket Number</div>
                        <div class="ticket-number-value">{{ $booking->ticket_number }}</div>
                    </div>
                </td>

                <td class="qr-column">
                    <div class="qr-panel">
                        @if ($qrCode['type'] === 'img')
                            <img src="{{ $qrCode['src'] }}" alt="Ticket QR Code" class="qr-image">
                        @else
                            {!! $qrCode['markup'] !!}
                        @endif
                    </div>

                    <div class="scan-label">Scan at Entrance</div>
                    <div class="scan-subtext">Present this code for<br>one-time entry verification</div>
                    <div class="confirmed-badge">{{ $entryBadge }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="ticket-footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">{{ config('app.name') }}</td>
                <td class="footer-right">
                    Non-transferable • Single entry only • Unauthorized duplication is prohibited
                </td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
