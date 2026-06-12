<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry received</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Inquiry</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">Your inquiry has been received</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi {{ $inquiry->user->first_name }},
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">
                                Thank you for reaching out. Our Customer Relations team has received your inquiry and will respond shortly.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border:1px solid #e2e8f0;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;background:#f8fafc;">
                                        <p style="margin:0 0 6px;font-size:16px;font-weight:700;">{{ $inquiry->subject }}</p>
                                        <p style="margin:0;font-size:13px;color:#64748b;">Event: {{ $inquiry->event->name }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0;font-size:14px;color:#334155;line-height:1.5;">{{ $inquiry->message }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#64748b;">
                                You can track your inquiry status from your EventHub support page.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
