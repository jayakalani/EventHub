<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $booking->ticket_number }}</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family: DejaVu Sans, sans-serif;
            background:#f1f5f9;
            color:#0f172a;
            padding:20px;
        }

        .ticket{
            width:100%;
            border-radius:20px;
            overflow:hidden;
            background:#ffffff;
            border:1px solid #e2e8f0;
        }

        /* =======================
           HEADER
        ======================== */

        .header{
            background:linear-gradient(135deg,#4f46e5,#7c3aed);
            color:white;
            padding:28px 30px;
        }

        .event-title{
            font-size:24px;
            font-weight:700;
            margin-bottom:8px;
        }

        .event-meta{
            font-size:11px;
            opacity:.9;
            line-height:1.6;
        }

        .status{
            display:inline-block;
            margin-top:12px;
            padding:6px 14px;
            background:rgba(255,255,255,.18);
            border:1px solid rgba(255,255,255,.25);
            border-radius:50px;
            font-size:10px;
            font-weight:700;
            letter-spacing:.08em;
        }

        /* =======================
           BODY
        ======================== */

        .body{
            padding:28px;
        }

        .section-title{
            font-size:12px;
            font-weight:700;
            color:#64748b;
            text-transform:uppercase;
            letter-spacing:.08em;
            margin-bottom:16px;
        }

        .details{
            width:100%;
            margin-bottom:24px;
        }

        .detail-row{
            display:table;
            width:100%;
            margin-bottom:12px;
            padding:10px 14px;
            background:#f8fafc;
            border:1px solid #e2e8f0;
            border-radius:10px;
        }

        .label{
            display:table-cell;
            width:35%;
            font-size:11px;
            color:#64748b;
            text-transform:uppercase;
            letter-spacing:.05em;
        }

        .value{
            display:table-cell;
            text-align:right;
            font-size:13px;
            font-weight:700;
            color:#0f172a;
        }

        /* =======================
           QR SECTION
        ======================== */

        .qr-wrapper{
            text-align:center;
            border:2px dashed #cbd5e1;
            border-radius:16px;
            padding:22px;
            background:#fafcff;
        }

        .qr-wrapper svg{
            width:140px;
            height:140px;
        }

        .ticket-qr-code{
            display:inline-block;
            font-size:0;
            line-height:0;
        }

        .ticket-qr-code div{
            height:4px;
            line-height:0;
        }

        .ticket-qr-code span{
            display:inline-block;
            width:4px;
            height:4px;
        }

        .qr-image{
            width:140px;
            height:140px;
        }

        .ticket-number{
            margin-top:12px;
            font-size:11px;
            color:#64748b;
        }

        .confirmed{
            display:inline-block;
            margin-top:14px;
            padding:7px 18px;
            background:#dcfce7;
            color:#15803d;
            border-radius:999px;
            font-size:11px;
            font-weight:700;
        }

        /* =======================
           FOOTER
        ======================== */

        .footer{
            border-top:1px solid #e2e8f0;
            background:#f8fafc;
            padding:18px 24px;
        }

        .footer-brand{
            font-size:12px;
            font-weight:700;
            color:#334155;
            margin-bottom:4px;
        }

        .footer-text{
            font-size:10px;
            color:#64748b;
            line-height:1.5;
        }

        .highlight{
            color:#4f46e5;
            font-weight:700;
        }
    </style>
</head>

<body>

<div class="ticket">

    <!-- HEADER -->
    <div class="header">

        <div class="event-title">
            {{ $booking->event->name }}
        </div>

        <div class="event-meta">
            📅 {{ $booking->event->date }}
            @if($booking->event->time)
                • {{ $booking->event->time }}
            @endif
            <br>
            📍 {{ $booking->event->place }}
        </div>

        <div class="status">
            EVENT PASS
        </div>

    </div>

    <!-- BODY -->
    <div class="body">

        <div class="section-title">
            Ticket Information
        </div>

        <div class="details">

            <div class="detail-row">
                <div class="label">Ticket Number</div>
                <div class="value">{{ $booking->ticket_number }}</div>
            </div>

            <div class="detail-row">
                <div class="label">Attendee</div>
                <div class="value">{{ $booking->user->full_name }}</div>
            </div>

            <div class="detail-row">
                <div class="label">Category</div>
                <div class="value">{{ $booking->ticketCategory->name }}</div>
            </div>

            <div class="detail-row">
                <div class="label">Payment Ref</div>
                <div class="value">
                    {{ $booking->payment->reference }}
                </div>
            </div>

            <div class="detail-row">
                <div class="label">Amount Paid</div>
                <div class="value">
                    Rs {{ number_format($booking->ticket_price, 2) }}
                </div>
            </div>

        </div>

        <!-- QR -->
        <div class="qr-wrapper">

            @if ($qrCode['type'] === 'img')
                <img src="{{ $qrCode['src'] }}" alt="Ticket QR Code" class="qr-image">
            @else
                {!! $qrCode['markup'] !!}
            @endif

            <div class="ticket-number">
                Scan at Event Entrance
            </div>

            <div class="confirmed">
                ✓ CONFIRMED ENTRY
            </div>

        </div>

    </div>

    <!-- FOOTER -->
    <div class="footer">

        <div class="footer-brand">
            {{ config('app.name') }}
        </div>

        <div class="footer-text">
            This ticket is valid for one-time entry only.
            Please present this QR code at the venue entrance for verification.
            Unauthorized duplication is prohibited.
        </div>

    </div>

</div>

</body>
</html>