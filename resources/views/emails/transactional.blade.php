<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;background:#090d16;color:#111827;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#090d16;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;overflow:hidden;border-radius:14px;background:#ffffff;">
                    <tr>
                        <td style="background:#111827;padding:24px 28px;">
                            <div style="font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#fca5a5;">BookMyMovie</div>
                            <h1 style="margin:10px 0 0;font-size:26px;line-height:1.2;color:#ffffff;">{{ $headline }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#374151;">{{ $intro }}</p>

                            @if(! empty($data))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;border-collapse:collapse;">
                                    @foreach($data as $label => $value)
                                        <tr>
                                            <td style="border-top:1px solid #e5e7eb;padding:12px 0;font-size:13px;font-weight:700;color:#6b7280;">{{ $label }}</td>
                                            <td align="right" style="border-top:1px solid #e5e7eb;padding:12px 0;font-size:14px;font-weight:700;color:#111827;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if($actionText && $actionUrl)
                                <p style="margin:24px 0;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;border-radius:8px;background:#dc2626;padding:13px 18px;font-size:14px;font-weight:800;color:#ffffff;text-decoration:none;">{{ $actionText }}</a>
                                </p>
                            @endif

                            <p style="margin:24px 0 0;font-size:12px;line-height:1.6;color:#6b7280;">
                                This is an automated email from BookMyMovie.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
