<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Job Pending Review</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px 20px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
        <div style="background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 32px 24px; text-align: center;">
            <h1 style="color: #ffffff; font-size: 22px; font-weight: 800; margin: 0;">{{ $brandName }} Admin</h1>
            <p style="color: rgba(255,255,255,0.85); font-size: 13px; margin: 8px 0 0;">New Job Pending Review</p>
        </div>
        <div style="padding: 32px 24px;">
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 16px;">Hello <strong>Admin</strong>,</p>
            <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 24px;">
                A new job has been posted by <strong>{{ $employerName }}</strong> and is pending your review.
            </p>
            <div style="background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 8px; padding: 16px; margin: 0 0 24px;">
                <p style="font-size: 13px; color: #1e40af; margin: 0; font-weight: 600;">Job Details:</p>
                <p style="font-size: 13px; color: #1e40af; margin: 8px 0 0; line-height: 1.5;">
                    <strong>Title:</strong> {{ $jobTitle }}<br>
                    <strong>Company:</strong> {{ $companyName }}<br>
                    <strong>Type:</strong> {{ $jobType }}
                </p>
            </div>
            <a href="{{ $adminUrl }}" style="display: inline-block; background: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">Review Job</a>
        </div>
        <div style="background: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #f1f5f9;">
            <p style="font-size: 11px; color: #94a3b8; margin: 0;">&copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
