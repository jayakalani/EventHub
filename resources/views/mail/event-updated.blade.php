<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event updated</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    @php
        $fieldLabels = [
            'name' => 'Event name',
            'date' => 'Date',
            'time' => 'Time',
            'place' => 'Venue',
            'description' => 'Description',
        ];
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0ea5e9,#0284c7);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Event updated</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">{{ $event->name }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                The organizer has updated event information for an event you have tickets for. Please review the changes below.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                                <tr>
                                    <td style="padding:20px 18px;">
                                        <p style="margin:0 0 12px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">What changed</p>
                                        @foreach($changes as $field => $change)
                                            <div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">
                                                <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#334155;">{{ $fieldLabels[$field] ?? ucfirst($field) }}</p>
                                                <p style="margin:0 0 2px;font-size:13px;color:#94a3b8;text-decoration:line-through;">{{ $change['old'] }}</p>
                                                <p style="margin:0;font-size:14px;color:#0f172a;font-weight:600;">{{ $change['new'] }}</p>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;">
                                <tr>
                                    <td style="padding:20px 18px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Current details</p>
                                        <p style="margin:0 0 4px;font-size:14px;color:#475569;">📅 {{ $event->date }}@if($event->time) • {{ $event->time }}@endif</p>
                                        <p style="margin:0;font-size:14px;color:#475569;">📍 {{ $event->place }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">
                                View the updated event on
                                <a href="{{ route('attendee.events.show', $event) }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">EventHub</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
