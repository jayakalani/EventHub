<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket purchase confirmation</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Payment confirmed</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">Your tickets are ready</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $payment->user->first_name }},
                            </p>

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                Thank you for your purchase. Your payment
                                <strong style="color:#0f172a;">{{ $payment->reference }}</strong>
                                for
                                <strong style="color:#0f172a;">Rs {{ number_format((float) $payment->amount, 2) }}</strong>
                                was successful. Your ticket PDFs are attached to this email.
                            </p>

                            @foreach ($groupedBookings as $eventBookings)
                                @php $event = $eventBookings->first()->event; @endphp
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                                    <tr>
                                        <td style="padding:16px 18px;background:#f8fafc;">
                                            <p style="margin:0 0 6px;font-size:16px;font-weight:700;color:#0f172a;">{{ $event->name }}</p>
                                            <p style="margin:0;font-size:13px;color:#64748b;">
                                                {{ $event->date }} · {{ $event->place }}
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 18px;">
                                            @foreach ($eventBookings as $booking)
                                                <p style="margin:0 0 8px;font-size:14px;line-height:1.5;color:#334155;">
                                                    <strong>{{ $booking->ticketCategory->name }}</strong>
                                                    · {{ $booking->ticket_number }}
                                                    · Rs {{ number_format((float) $booking->ticket_price, 2) }}
                                                </p>
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>
                            @endforeach

                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#475569;">
                                You can also view and download your tickets anytime from your
                                <a href="{{ route('attendee.bookings.index') }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">My Tickets</a>
                                page.
                            </p>

                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                                Present your ticket QR code at the event entrance. If you have questions, reply to this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;color:#94a3b8;text-align:center;">
                            {{ config('app.name') }} · Ticket purchase confirmation
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
