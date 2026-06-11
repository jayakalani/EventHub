<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event cancelled</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#e11d48,#f43f5e);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Event cancelled</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">{{ $event->name }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                We are sorry to inform you that the following event has been cancelled by the organizer.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                                <tr>
                                    <td style="padding:20px 18px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Event details</p>
                                        <p style="margin:0 0 6px;font-size:15px;font-weight:700;color:#0f172a;">{{ $event->name }}</p>
                                        <p style="margin:0 0 4px;font-size:14px;color:#475569;">📅 {{ $event->date }}@if($event->time) • {{ $event->time }}@endif</p>
                                        <p style="margin:0;font-size:14px;color:#475569;">📍 {{ $event->place }}</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #fecdd3;border-radius:12px;background:#fff1f2;">
                                <tr>
                                    <td style="padding:20px 18px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#be123c;text-transform:uppercase;letter-spacing:0.06em;">Reason for cancellation</p>
                                        <p style="margin:0;font-size:15px;line-height:1.6;color:#881337;">{{ $cancellationReason }}</p>
                                    </td>
                                </tr>
                            </table>

                            @if($refundTotal > 0)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #d1fae5;border-radius:12px;background:#ecfdf5;">
                                    <tr>
                                        <td style="padding:20px 18px;text-align:center;">
                                            <p style="margin:0 0 6px;font-size:13px;color:#047857;text-transform:uppercase;letter-spacing:0.06em;">Refund processed</p>
                                            <p style="margin:0;font-size:32px;font-weight:700;color:#059669;">
                                                Rs {{ number_format($refundTotal, 2) }}
                                            </p>
                                            <p style="margin:12px 0 0;font-size:14px;color:#047857;">
                                                Full refund credited to your wallet.
                                                @if($user->wallet)
                                                    New balance: Rs {{ number_format((float) $user->wallet->balance, 2) }}
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                                    Your ticket(s) for this event have been refunded in full. You can view your wallet balance on the
                                    <a href="{{ route('attendee.wallet.index') }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">Wallet</a>
                                    page or check your booking history.
                                </p>
                            @else
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                                    No ticket purchase was found on your account for this event. You can still view the event details in your history.
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
