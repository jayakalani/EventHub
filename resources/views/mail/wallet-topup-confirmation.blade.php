<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet top-up confirmed</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Wallet top-up</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">Funds added successfully</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $payment->user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                Your wallet top-up of
                                <strong>Rs {{ number_format((float) $payment->amount, 2) }}</strong>
                                (reference {{ $payment->reference }}) was successful.
                            </p>
                            <p style="margin:0;font-size:15px;line-height:1.6;color:#334155;">
                                <strong>Current wallet balance:</strong>
                                Rs {{ number_format((float) ($payment->user->wallet->balance ?? 0), 2) }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
