<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly organizer digest</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0F0363,#2A1585);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Weekly digest</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">{{ $digest['weekLabel'] }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                Here’s how your events performed over the last 7 days.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                                <tr>
                                    <td width="50%" style="padding:0 6px 12px 0;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:16px;">
                                            <p style="margin:0 0 6px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Net revenue</p>
                                            <p style="margin:0;font-size:20px;font-weight:700;color:#059669;">LKR {{ number_format($digest['netRevenue'], 0) }}</p>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding:0 0 12px 6px;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:16px;">
                                            <p style="margin:0 0 6px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Tickets sold</p>
                                            <p style="margin:0;font-size:20px;font-weight:700;color:#0f172a;">{{ number_format($digest['ticketsSold']) }}</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:0 6px 0 0;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:16px;">
                                            <p style="margin:0 0 6px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Attendance rate</p>
                                            <p style="margin:0;font-size:20px;font-weight:700;color:#0f172a;">
                                                @if ($digest['attendanceRate'] !== null)
                                                    {{ number_format($digest['attendanceRate'], 1) }}%
                                                @else
                                                    —
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding:0 0 0 6px;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;padding:16px;">
                                            <p style="margin:0 0 6px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Checked in</p>
                                            <p style="margin:0;font-size:20px;font-weight:700;color:#0f172a;">{{ number_format($digest['checkedIn']) }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            @if ($digest['topEvent'])
                                <div style="margin-bottom:16px;border:1px solid #d1fae5;border-radius:12px;background:#ecfdf5;padding:16px;">
                                    <p style="margin:0 0 6px;font-size:12px;color:#047857;text-transform:uppercase;letter-spacing:0.06em;">Top event</p>
                                    <p style="margin:0 0 4px;font-size:15px;font-weight:700;color:#065f46;">{{ $digest['topEvent']['name'] }}</p>
                                    <p style="margin:0;font-size:13px;color:#047857;">
                                        LKR {{ number_format($digest['topEvent']['revenue'], 0) }}
                                        · {{ number_format($digest['topEvent']['tickets_sold']) }} tickets
                                    </p>
                                </div>
                            @endif

                            @if ($digest['bottomEvent'])
                                <div style="margin-bottom:24px;border:1px solid #fee2e2;border-radius:12px;background:#fff1f2;padding:16px;">
                                    <p style="margin:0 0 6px;font-size:12px;color:#be123c;text-transform:uppercase;letter-spacing:0.06em;">Needs attention</p>
                                    <p style="margin:0 0 4px;font-size:15px;font-weight:700;color:#9f1239;">{{ $digest['bottomEvent']['name'] }}</p>
                                    <p style="margin:0;font-size:13px;color:#be123c;">
                                        LKR {{ number_format($digest['bottomEvent']['revenue'], 0) }}
                                        · {{ number_format($digest['bottomEvent']['tickets_sold']) }} tickets
                                    </p>
                                </div>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="border-radius:12px;background:#0F0363;">
                                        <a href="{{ $digest['reportsUrl'] }}"
                                            style="display:inline-block;padding:14px 22px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                                            Open full reports
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                                You’re receiving this because you organize events on EventHub.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
