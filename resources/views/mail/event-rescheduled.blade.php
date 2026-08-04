<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Rescheduled</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4f46e5,#6366f1);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Event Rescheduled</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">{{ $event->name }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hello {{ $user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                Your postponed event has now been rescheduled.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                                <tr>
                                    <td style="padding:20px 18px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Event details</p>
                                        <p style="margin:0 0 6px;font-size:15px;font-weight:700;color:#0f172a;">{{ $event->name }}</p>
                                        <p style="margin:0 0 4px;font-size:14px;color:#475569;">
                                            📅 {{ $event->formattedScheduleDate() ?? $event->date }}
                                            @if($event->time) • {{ $event->time }}@endif
                                        </p>
                                        <p style="margin:0;font-size:14px;color:#475569;">📍 {{ $event->place }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">
                                Your ticket remains valid for the new date. View details in
                                <a href="{{ route('attendee.bookings.index') }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">My Bookings</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
