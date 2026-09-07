<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Scheduled</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
        <div style="background: linear-gradient(135deg, #0891b2, #06b6d4); padding: 32px 24px; text-align: center;">
            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0;">{{ $brandName }}</h1>
            <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 8px 0 0;">Interview Invitation</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
                Hello <strong>{{ $userName }}</strong>,
            </p>
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
                Great news! <strong>{{ $companyName }}</strong> has scheduled an interview for the <strong>{{ $jobTitle }}</strong> position.
            </p>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
                <table style="width: 100%; font-size: 13px; color: #065f46;">
                    <tr><td style="padding: 4px 0; font-weight: 600;">Date:</td><td>{{ $interviewDate }}</td></tr>
                    <tr><td style="padding: 4px 0; font-weight: 600;">Time:</td><td>{{ $interviewTime }}</td></tr>
                    <tr><td style="padding: 4px 0; font-weight: 600;">Type:</td><td>{{ $interviewType }}</td></tr>
                    @if(isset($location))<tr><td style="padding: 4px 0; font-weight: 600;">Location:</td><td>{{ $location }}</td></tr>@endif
                </table>
            </div>
            <div style="text-align: center; margin: 0 0 16px;">
                <a href="{{ $acceptUrl }}" style="display: inline-block; background: #22c55e; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; margin: 0 4px;">Accept</a>
                <a href="{{ $declineUrl }}" style="display: inline-block; background: #ef4444; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; margin: 0 4px;">Decline</a>
            </div>
        </div>
        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #f1f5f9;">
            <p style="font-size: 11px; color: #94a3b8; margin: 0;">&copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
