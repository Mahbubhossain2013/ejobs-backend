<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Code</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 32px 24px; text-align: center;">
            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">
                {{ $brandName }}
            </h1>
            <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 8px 0 0; font-weight: 500;">
                Email Verification Code
            </p>
        </div>

        <!-- Body -->
        <div style="padding: 32px 24px;">
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
                Hello <strong>{{ $userName }}</strong>,
            </p>
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
                Use the following verification code to confirm your email address. This code expires in <strong>10 minutes</strong>.
            </p>

            <!-- OTP Code Block -->
            <div style="background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; margin: 0 0 24px;">
                <span style="font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #1e293b; font-family: 'Courier New', monospace;">
                    {{ $otpCode }}
                </span>
            </div>

            <p style="font-size: 12px; color: #94a3b8; line-height: 1.6; margin: 0;">
                If you did not request this code, please ignore this email. Do not share this code with anyone.
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #f1f5f9;">
            <p style="font-size: 11px; color: #94a3b8; margin: 0; font-weight: 600;">
                &copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
