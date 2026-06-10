<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund approved</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#059669,#10b981);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Refund approved</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">Credited to your wallet</h1>
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
                                has been <strong style="color:#059669;">approved</strong>.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #d1fae5;border-radius:12px;background:#ecfdf5;">
                                <tr>
                                    <td style="padding:20px 18px;text-align:center;">
                                        <p style="margin:0 0 6px;font-size:13px;color:#047857;text-transform:uppercase;letter-spacing:0.06em;">Refund credited</p>
                                        <p style="margin:0;font-size:32px;font-weight:700;color:#059669;">
                                            Rs {{ number_format((float) $refundRequest->refund_amount, 2) }}
                                        </p>
                                        <p style="margin:12px 0 0;font-size:14px;color:#047857;">
                                            Wallet balance: Rs {{ number_format((float) ($refundRequest->user->wallet->balance ?? 0), 2) }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                                You can use your wallet balance to purchase tickets or top up anytime from your
                                <a href="{{ route('attendee.wallet.index') }}" style="color:#4f46e5;text-decoration:none;font-weight:600;">Wallet</a>
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
