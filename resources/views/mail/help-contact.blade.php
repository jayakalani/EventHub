<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help contact form</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0F0363,#4f46e5);padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 8px;font-size:13px;opacity:0.9;text-transform:uppercase;letter-spacing:0.08em;">Help Center</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;">New contact form message</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;">
                                Someone submitted the public Help / FAQ contact form.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:14px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0 0 4px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Name</p>
                                        <p style="margin:0;font-size:15px;font-weight:700;">{{ $senderName }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0 0 4px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Email</p>
                                        <p style="margin:0;font-size:15px;">
                                            <a href="mailto:{{ $senderEmail }}" style="color:#4f46e5;text-decoration:none;">{{ $senderEmail }}</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <p style="margin:0 0 8px;font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Comment</p>
                                        <p style="margin:0;font-size:15px;line-height:1.6;color:#334155;white-space:pre-wrap;">{{ $comment }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                                You can reply directly to this email to reach {{ $senderName }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
