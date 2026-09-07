<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Arch Ribbon Grey</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #262626; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: linear-gradient(to right, #6b7280 38%, #ffffff 38%);
            display: flex;
            align-items: stretch;
        }
        @media print {
            html, body {
                background: linear-gradient(to right, #6b7280 38%, #ffffff 38%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .cv-page {
                width: 100%;
                min-height: 297mm;
                margin: 0;
                background: linear-gradient(to right, #6b7280 38%, #ffffff 38%) !important;
                box-shadow: none;
            }
        }

        /* Left Column with Arch Pillar */
        .left-col {
            width: 38%;
            background: #6b7280;
            color: #fff;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
            align-self: stretch;
            min-height: 100%;
        }
        
        .arch-top { background: #9ca3af; border-top-left-radius: 90px; border-top-right-radius: 90px; padding: 18px 12px 14px; text-align: center; margin-bottom: 4px; }
        .avatar-circle { width: 110px; height: 110px; border-radius: 50%; border: 3px solid #fff; overflow: hidden; background: #111; margin: 0 auto; }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 800; color: #fff; }

        /* Pop-out Folded Ribbon Box */
        .ribbon-banner { background: #374151; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 1px; text-align: center; padding: 8px 12px; margin: 0 -26px 8px -26px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); position: relative; border-radius: 3px; }
        
        .left-desc { font-size: 9.5px; line-height: 1.5; color: #f3f4f6; }
        
        .bullet-list { list-style: none; padding: 0; }
        .bullet-list li { font-size: 10px; color: #f3f4f6; margin-bottom: 4px; position: relative; padding-left: 12px; }
        .bullet-list li::before { content: "•"; position: absolute; left: 0; color: #fff; font-weight: 900; }

        .contact-row { display: flex; align-items: center; gap: 8px; font-size: 9.5px; color: #f3f4f6; margin-bottom: 6px; word-break: break-all; }

        /* Right Column (White) */
        .right-col { width: 62%; background: #fff; color: #000; padding: 32px 28px; display: flex; flex-direction: column; gap: 18px; }
        .name-head { font-family: 'Montserrat', sans-serif; font-size: 28px; font-weight: 900; color: #111; margin-bottom: 2px; }
        .name-sub { font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 12px; }

        .sec-banner-right { background: #4b5563; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; padding: 6px 14px; margin-bottom: 12px; margin-left: -28px; padding-left: 28px; }

        .timeline-item { position: relative; padding-left: 18px; margin-bottom: 12px; border-left: 1.5px solid #9ca3af; margin-left: 6px; page-break-inside: avoid; break-inside: avoid; }
        .timeline-item::before { content: ''; position: absolute; left: -5.5px; top: 2px; width: 9px; height: 9px; border-radius: 50%; background: #111; }

        .item-title { font-family: 'Montserrat', sans-serif; font-size: 11.5px; font-weight: 800; color: #111; }
        .item-sub { font-size: 10px; font-weight: 600; color: #4b5563; margin-bottom: 2px; }
        .item-desc { font-size: 9.5px; line-height: 1.5; color: #4b5563; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="left-col">
        <div class="arch-top">
            <div class="avatar-circle">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'Y', 0, 1)) }}</div>
                @endif
            </div>
        </div>

        @if(!empty($summary))
        <div>
            <div class="ribbon-banner">About Me</div>
            <div class="left-desc">{{ $summary }}</div>
        </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="ribbon-banner">Skills</div>
            <ul class="bullet-list">
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                @if($sn)<li>{{ $sn }}</li>@endif
                @endforeach
            </ul>
        </div>
        @endif

        <div>
            <div class="ribbon-banner">Contact Me</div>
            @if(!empty($candidate['phone']))
            <div class="contact-row">📞 {{ $candidate['phone'] }}</div>
            @endif
            @if(!empty($candidate['email']))
            <div class="contact-row">✉️ {{ $candidate['email'] }}</div>
            @endif
            @if(!empty($candidate['website']))
            <div class="contact-row">🌐 {{ $candidate['website'] }}</div>
            @endif
            @if(!empty($candidate['location']))
            <div class="contact-row">📍 {{ $candidate['location'] }}</div>
            @endif
        </div>
    </div>

    <div class="right-col">
        <div>
            <h1 class="name-head">{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            <div class="name-sub">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-banner-right">Education</div>
            @foreach($education as $ed)
            <div class="timeline-item">
                <div class="item-title">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                <div class="item-sub">{{ $ed['institution'] ?? $ed['school'] ?? '' }} @if(!empty($ed['start_date']))| {{ $ed['start_date'] }}@if(!empty($ed['end_date'])) - {{ $ed['end_date'] }}@endif @endif</div>
                @if(!empty($ed['description']))<div class="item-desc">{{ $ed['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-banner-right">Work Experience</div>
            @foreach($experience as $e)
            <div class="timeline-item">
                <div class="item-title">{{ $e['position'] ?? $e['title'] ?? '' }}</div>
                <div class="item-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }} @if(!empty($e['location']))| {{ $e['location'] }}@endif @if(!empty($e['start_date']))| {{ $e['start_date'] }}@if(!empty($e['end_date'])) - {{ $e['end_date'] }}@else - Present @endif @endif</div>
                @if(!empty($e['description']))<div class="item-desc">{{ $e['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>