<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund request update</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#dc2626,#ef4444);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Refund update</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">Request not approved</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $refundRequest->user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                Your refund request for ticket
                                <strong>{{ $refundRequest->ticketBooking->ticket_number }}</strong>
                                ({{ $refundRequest->ticketBooking->event->name }})
                                has been <strong style="color:#dc2626;">declined</strong>.
                            </p>
                            @if($refundRequest->status->value === 'auto_declined')
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                                    Refunds are not available after the event date per our refund policy.
                                </p>
                            @elseif($refundRequest->cro_notes)
                                <div style="margin:0 0 16px;padding:16px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;">
                                    <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:0.05em;">
                                        Reason for decline
                                    </p>
                                    <p style="margin:0;font-size:14px;line-height:1.6;color:#7f1d1d;">
                                        {{ $refundRequest->cro_notes }}
                                    </p>
                                </div>
                            @endif
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#64748b;">
                                If you have questions, please contact our support team.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
