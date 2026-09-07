<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Deployment Update' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4f8; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0891b2, #0e7490); padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">✈️ Deployment Update</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 36px 40px;">
                            <p style="color: #334155; font-size: 16px; margin: 0 0 16px;">Hello <strong>{{ $userName }}</strong>,</p>

                            <p style="color: #475569; font-size: 15px; line-height: 1.7; margin: 0 0 24px;">
                                {{ $message }}
                            </p>

                            @if(!empty($stageName))
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0fdfa; border-radius: 8px; border: 1px solid #99f6e4; margin: 0 0 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="color: #0f766e; font-size: 14px; margin: 0 0 8px; font-weight: 600;">Deployment Stage</p>
                                        <p style="color: #1e293b; font-size: 16px; font-weight: 700; margin: 0;">{{ $stageName }}</p>
                                        @if(!empty($stageStatus))
                                        <p style="color: #64748b; font-size: 13px; margin: 8px 0 0;">Status: <strong>{{ ucfirst($stageStatus) }}</strong></p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                            @endif

                            @if(!empty($jobTitle))
                            <p style="color: #64748b; font-size: 14px; margin: 0 0 8px;">Project: <strong>{{ $jobTitle }}</strong></p>
                            @endif

                            @if(!empty($destinationCountry))
                            <p style="color: #64748b; font-size: 14px; margin: 0 0 24px;">Destination: <strong>{{ $destinationCountry }}</strong></p>
                            @endif

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $deploymentUrl ?? '#' }}" style="display: inline-block; background: linear-gradient(135deg, #0891b2, #0e7490); color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 600; font-size: 15px;">View Deployment</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0; text-align: center;">
                                &copy; {{ date('Y') }} {{ $brandName ?? config('app.name', 'eJobs') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
