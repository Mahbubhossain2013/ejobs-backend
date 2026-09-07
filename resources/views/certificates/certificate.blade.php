<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - {{ $certificate_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .certificate {
            width: 100%;
            min-height: 100vh;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .certificate-inner {
            width: 100%;
            max-width: 900px;
            aspect-ratio: 1.414 / 1;
            position: relative;
            background: {{ $background_color ?: '#fefefe' }};
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        /* Outer decorative border */
        .border-outer {
            position: absolute;
            inset: 12px;
            border: 2px solid {{ $primary_color ?: '#1a3c6c' }};
            pointer-events: none;
        }

        /* Inner decorative border */
        .border-inner {
            position: absolute;
            inset: 18px;
            border: 1px solid {{ $accent_color ?: '#c9a84c' }};
            pointer-events: none;
        }

        /* Corner ornaments */
        .corner {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 2px solid {{ $accent_color ?: '#c9a84c' }};
            pointer-events: none;
        }
        .corner-tl { top: 20px; left: 20px; border-right: none; border-bottom: none; }
        .corner-tr { top: 20px; right: 20px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 20px; left: 20px; border-right: none; border-top: none; }
        .corner-br { bottom: 20px; right: 20px; border-left: none; border-top: none; }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-family: 'Cinzel', serif;
            font-size: 140px;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.018);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            letter-spacing: 8px;
        }

        .content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 50px 60px 40px;
        }

        /* Header */
        .header {
            text-align: center;
            width: 100%;
        }

        .logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .logo-area img {
            max-height: 48px;
            max-width: 140px;
            object-fit: contain;
        }

        .logo-placeholder {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: {{ $primary_color ?: '#1a3c6c' }};
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-family: 'Cinzel', serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .org-name {
            font-family: 'Cinzel', serif;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: {{ $primary_color ?: '#1a3c6c' }};
            margin-top: 6px;
        }

        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 32px;
            font-weight: 700;
            color: {{ $primary_color ?: '#1a3c6c' }};
            text-transform: uppercase;
            letter-spacing: 6px;
            margin-top: 16px;
        }

        .cert-subtitle {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-style: italic;
            color: #666;
            margin-top: 6px;
            letter-spacing: 1px;
        }

        .gold-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 18px 0;
            width: 100%;
        }

        .gold-divider .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, {{ $accent_color ?: '#c9a84c' }}, transparent);
        }

        .gold-divider .diamond {
            width: 8px;
            height: 8px;
            background: {{ $accent_color ?: '#c9a84c' }};
            transform: rotate(45deg);
        }

        /* Body */
        .body {
            text-align: center;
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 14px;
        }

        .preamble {
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            color: #666;
            font-style: italic;
            letter-spacing: 1px;
        }

        .recipient-label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #999;
            margin-top: 10px;
        }

        .recipient-name {
            font-family: 'Cinzel', serif;
            font-size: 38px;
            font-weight: 700;
            color: {{ $primary_color ?: '#1a3c6c' }};
            margin: 8px 0 16px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid {{ $accent_color ?: '#c9a84c' }};
            display: inline-block;
            min-width: 300px;
        }

        .course-label {
            font-family: 'Inter', sans-serif;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #999;
            margin-top: 6px;
        }

        .course-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: #222;
            margin-top: 4px;
        }

        .instructor-row {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #777;
            margin-top: 4px;
        }

        /* Score */
        .score-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            padding: 6px 18px;
            border: 1.5px solid {{ $accent_color ?: '#c9a84c' }};
            border-radius: 4px;
            background: rgba(201, 168, 76, 0.06);
        }

        .score-badge .label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #888;
        }

        .score-badge .value {
            font-family: 'Cinzel', serif;
            font-size: 18px;
            font-weight: 700;
            color: {{ $primary_color ?: '#1a3c6c' }};
        }

        /* Footer */
        .footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
            padding-top: 18px;
            border-top: 1px solid #e5e5e5;
        }

        .footer-col {
            text-align: center;
            min-width: 160px;
        }

        .footer-col.left { text-align: left; }
        .footer-col.right { text-align: right; }

        .footer-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #999;
            margin-bottom: 10px;
        }

        .signature-line {
            width: 140px;
            height: 1px;
            background: #333;
            margin-bottom: 6px;
        }

        .signature-name {
            font-family: 'Cinzel', serif;
            font-size: 12px;
            font-weight: 600;
            color: #222;
        }

        .signature-title {
            font-size: 10px;
            color: #888;
            margin-top: 2px;
        }

        .qr-area {
            width: 60px;
            height: 60px;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #bbb;
            margin: 0 auto 6px;
        }

        /* Seal */
        .seal {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid {{ $accent_color ?: '#c9a84c' }};
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ $primary_color ?: '#1a3c6c' }};
            text-align: center;
            line-height: 1.2;
            opacity: 0.8;
            position: absolute;
            bottom: 30px;
            right: 40px;
        }

        .meta-row {
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
            margin-top: auto;
            border-top: 1px solid #f0f0f0;
        }

        .meta-item {
            text-align: center;
        }

        .meta-label {
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #bbb;
        }

        .meta-value {
            font-size: 10px;
            color: #777;
            margin-top: 2px;
            font-family: 'Inter', sans-serif;
        }

        @media print {
            body { background: white; }
            .certificate-inner { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="certificate-inner">
            <div class="border-outer"></div>
            <div class="border-inner"></div>
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>
            <div class="watermark">CERTIFIED</div>
            <div class="seal">eJobs<br>Verified</div>

            <div class="content">
                <div class="header">
                    <div class="logo-area">
                        @if($logo)
                            <img src="{{ $logo }}" alt="Logo">
                        @else
                            <div class="logo-placeholder">{{ substr(config('app.name', 'eJobs'), 0, 2) }}</div>
                        @endif
                    </div>
                    <div class="org-name">{{ config('app.name', 'eJobs') }}</div>
                    <div class="cert-title">Certificate</div>
                    <div class="cert-subtitle">of Professional Achievement</div>

                    <div class="gold-divider">
                        <div class="line"></div>
                        <div class="diamond"></div>
                        <div class="line"></div>
                    </div>
                </div>

                <div class="body">
                    <p class="preamble">This certificate is proudly presented to</p>

                    <div class="recipient-label">Recipient</div>
                    <div class="recipient-name">{{ $recipient_name }}</div>

                    <div class="course-label">For successfully completing</div>
                    <div class="course-title">{{ $course_title }}</div>

                    @if($instructor ?? null)
                        <div class="instructor-row">Instructor: {{ $instructor }}</div>
                    @endif

                    @if($score)
                    <div class="score-badge">
                        <span class="label">Score</span>
                        <span class="value">{{ $score }}%</span>
                    </div>
                    @endif
                </div>

                <div class="footer">
                    <div class="footer-col left">
                        <div class="footer-label">Date of Issue</div>
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $issued_at }}</div>
                    </div>

                    <div class="footer-col">
                        <div class="qr-area">QR</div>
                        <div class="footer-label">Verify</div>
                        <div class="signature-name" style="font-size: 9px;">{{ $certificate_number }}</div>
                    </div>

                    <div class="footer-col right">
                        <div class="footer-label">Authorized Signature</div>
                        <div class="signature-line"></div>
                        <div class="signature-name">Program Director</div>
                        <div class="signature-title">eJobs Skill Center</div>
                    </div>
                </div>

                <div class="meta-row">
                    <div class="meta-item">
                        <div class="meta-label">Certificate ID</div>
                        <div class="meta-value">{{ $certificate_number }}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Issued</div>
                        <div class="meta-value">{{ $issued_at }}</div>
                    </div>
                    @if($expires_at)
                    <div class="meta-item">
                        <div class="meta-label">Valid Until</div>
                        <div class="meta-value">{{ $expires_at }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
