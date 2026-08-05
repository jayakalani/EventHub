<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate this event</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0F0363,#2A1585);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Share your feedback</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">How was {{ $event->name }}?</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                It’s been about a day since {{ $event->name }}. A quick rating and comment helps other attendees and the host.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                                <tr>
                                    <td style="padding:20px 18px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Event details</p>
                                        <p style="margin:0 0 6px;font-size:15px;font-weight:700;color:#0f172a;">{{ $event->name }}</p>
                                        @if($event->host)
                                            <p style="margin:0 0 6px;font-size:14px;color:#475569;">Hosted by {{ $event->host->name }}</p>
                                        @endif
                                        <p style="margin:0 0 4px;font-size:14px;color:#475569;">📅 {{ $event->date }}@if($event->time) • {{ $event->time }}@endif</p>
                                        <p style="margin:0;font-size:14px;color:#475569;">📍 {{ $event->place }}</p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="border-radius:12px;background:#0F0363;">
                                        <a href="{{ route('attendee.events.show', $event) }}#ratings"
                                            style="display:inline-block;padding:14px 22px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                                            Rate &amp; comment
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                                Takes less than a minute. Thank you for being part of EventHub.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
