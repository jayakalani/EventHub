<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund request received</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Refund request</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">We've received your request</h1>
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
                                has been submitted and is pending review by our Customer Relations team.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;background:#f8fafc;">
                                        <p style="margin:0 0 6px;font-size:16px;font-weight:700;">{{ $refundRequest->ticketBooking->event->name }}</p>
                                        <p style="margin:0;font-size:13px;color:#64748b;">
                                            {{ $refundRequest->ticketBooking->ticketCategory->name }}
                                            · Rs {{ number_format((float) $refundRequest->ticketBooking->ticket_price, 2) }}
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0 0 8px;font-size:14px;"><strong>Your reason:</strong></p>
                                        <p style="margin:0;font-size:14px;color:#334155;line-height:1.5;">{{ $refundRequest->reason }}</p>
                                        <p style="margin:16px 0 0;font-size:14px;color:#334155;">
                                            <strong>Estimated refund:</strong>
                                            {{ $refundRequest->refund_percentage }}%
                                            (Rs {{ number_format((float) $refundRequest->refund_amount, 2) }})
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#64748b;">
                                You will receive another email once a decision has been made.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
