<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $brandName }}</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
        <div style="background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 32px 24px; text-align: center;">
            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0;">Welcome to {{ $brandName }}!</h1>
            <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 8px 0 0;">Your journey begins here</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 16px;">
                Hello <strong>{{ $userName }}</strong>,
            </p>
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
                Welcome aboard! Your account has been created successfully. Here's what you can do to get started:
            </p>
            <div style="margin: 0 0 24px;">
                <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;">
                    <div style="width: 28px; height: 28px; background: #ede9fe; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px; flex-shrink: 0;">1</div>
                    <div>
                        <p style="font-size: 13px; color: #334155; margin: 0; font-weight: 600;">Complete Your Profile</p>
                        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">Add your skills, experience, and resume to stand out.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;">
                    <div style="width: 28px; height: 28px; background: #ede9fe; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px; flex-shrink: 0;">2</div>
                    <div>
                        <p style="font-size: 13px; color: #334155; margin: 0; font-weight: 600;">Browse Jobs</p>
                        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">Explore thousands of opportunities matched to your skills.</p>
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 28px; height: 28px; background: #ede9fe; border-radius: 50%; text-align: center; line-height: 28px; font-size: 14px; flex-shrink: 0;">3</div>
                    <div>
                        <p style="font-size: 13px; color: #334155; margin: 0; font-weight: 600;">Apply & Grow</p>
                        <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">Apply to jobs and track your career progress.</p>
                    </div>
                </div>
            </div>
            <a href="{{ $dashboardUrl }}" style="display: inline-block; background: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">Go to Dashboard</a>
        </div>
        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #f1f5f9;">
            <p style="font-size: 11px; color: #94a3b8; margin: 0;">&copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
