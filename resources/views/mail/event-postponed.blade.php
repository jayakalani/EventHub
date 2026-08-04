<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Postponed</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#d97706,#f59e0b);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Event Postponed</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">{{ $event->name }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hello {{ $user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                The event
                                <strong style="color:#0f172a;">{{ $event->name }}</strong>
                                has been postponed.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #fde68a;border-radius:12px;background:#fffbeb;">
                                <tr>
                                    <td style="padding:20px 18px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#b45309;text-transform:uppercase;letter-spacing:0.06em;">Reason</p>
                                        <p style="margin:0;font-size:15px;line-height:1.6;color:#92400e;">{{ $postponementReason }}</p>
                                    </td>
                                </tr>
                            </table>

                            @if($event->hasDateYetToBeScheduled())
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                                    <tr>
                                        <td style="padding:20px 18px;">
                                            <p style="margin:0 0 8px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Schedule</p>
                                            <p style="margin:0;font-size:15px;font-weight:700;color:#0f172a;">The new event date will be announced soon.</p>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#475569;">
                                    Your ticket remains valid until further notice.
                                </p>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                                    If you prefer not to wait, you may request a FULL refund.
                                </p>
                            @else
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                                    <tr>
                                        <td style="padding:20px 18px;">
                                            <p style="margin:0 0 8px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">New Date</p>
                                            <p style="margin:0;font-size:18px;font-weight:700;color:#0f172a;">
                                                {{ $event->formattedScheduleDate() }}
                                                @if($event->time)
                                                    <span style="font-size:14px;font-weight:500;color:#475569;"> • {{ $event->time }}</span>
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#475569;">
                                    Your ticket remains valid.
                                </p>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                                    If you cannot attend the new date, you may request a FULL refund.
                                </p>
                            @endif

                            <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">
                                Manage your tickets from your
                                <a href="{{ route('attendee.bookings.index') }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">My Bookings</a>
                                page.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
