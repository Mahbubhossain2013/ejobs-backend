<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Verification</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 32px 24px; text-align: center;">
            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">
                {{ $brandName }}
            </h1>
            <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 8px 0 0; font-weight: 500;">
                Complete Your Account Verification
            </p>
        </div>

        <!-- Body -->
        <div style="padding: 32px 24px;">
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
                Hello <strong>{{ $userName }}</strong>,
            </p>
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
                Your account verification is <strong>{{ $percentage }}% complete</strong>. To unlock full platform features including job applications, wallet access, and trust badges, please complete the remaining steps.
            </p>

            <!-- Status Steps -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 0 0 24px;">
                @if($emailVerified)
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="color: #16a34a; font-size: 16px;">&#10003;</span>
                    <span style="font-size: 13px; color: #16a34a; font-weight: 600;">Email Verified</span>
                </div>
                @else
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="color: #f59e0b; font-size: 16px;">&#9679;</span>
                    <span style="font-size: 13px; color: #92400e; font-weight: 600;">Email — Not Verified</span>
                </div>
                @endif

                @if($phoneVerified)
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="color: #16a34a; font-size: 16px;">&#10003;</span>
                    <span style="font-size: 13px; color: #16a34a; font-weight: 600;">Phone Verified</span>
                </div>
                @else
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="color: #f59e0b; font-size: 16px;">&#9679;</span>
                    <span style="font-size: 13px; color: #92400e; font-weight: 600;">Phone — Not Verified</span>
                </div>
                @endif

                @if($nidVerified)
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #16a34a; font-size: 16px;">&#10003;</span>
                    <span style="font-size: 13px; color: #16a34a; font-weight: 600;">NID Verified</span>
                </div>
                @else
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #f59e0b; font-size: 16px;">&#9679;</span>
                    <span style="font-size: 13px; color: #92400e; font-weight: 600;">NID — Not Verified</span>
                </div>
                @endif
            </div>

            <!-- CTA Button -->
            <div style="text-align: center; margin: 0 0 24px;">
                <a href="{{ $verifyUrl }}" style="display: inline-block; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 14px; font-weight: 700; letter-spacing: -0.3px;">
                    Complete Verification
                </a>
            </div>

            <p style="font-size: 12px; color: #94a3b8; line-height: 1.6; margin: 0;">
                If you did not create this account, please ignore this email.
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
