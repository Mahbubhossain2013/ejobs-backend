<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Update</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
        <div style="background: linear-gradient(135deg, {{ $color }}, {{ $colorDark }}); padding: 32px 24px; text-align: center;">
            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0;">{{ $brandName }}</h1>
            <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 8px 0 0;">{{ $subject }}</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 16px;">Hello <strong>{{ $recipientName }}</strong>,</p>
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">{{ $message }}</p>
            @if(!empty($detail))
            <div style="background: {{ $detailBg }}; border-left: 4px solid {{ $color }}; border-radius: 8px; padding: 16px; margin: 0 0 24px;">
                <p style="font-size: 13px; color: {{ $detailColor }}; margin: 0; line-height: 1.5;">{{ $detail }}</p>
            </div>
            @endif
            <a href="{{ $dashboardUrl }}" style="display: inline-block; background: {{ $color }}; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">{{ $buttonText }}</a>
        </div>
        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #f1f5f9;">
            <p style="font-size: 11px; color: #94a3b8; margin: 0;">&copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
