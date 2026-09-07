<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Monochrome Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #262626; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; height: auto; page-break-after: auto; } }

        /* Left Column (Dark Slate #383e45) */
        .left-col { width: 38%; background: #383e45; color: #fff; padding: 32px 20px; display: flex; flex-direction: column; gap: 20px; }
        
        .avatar-wrap { width: 130px; height: 130px; border-radius: 50%; border: 4px solid #fff; margin: 0 auto 12px; overflow: hidden; background: #1c1917; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 46px; font-weight: 800; color: #fff; }

        .badge-bar { background: #555e68; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; padding: 5px 12px; margin-bottom: 8px; }
        .left-text { font-size: 9.5px; line-height: 1.55; color: #e2e8f0; }

        .contact-item { display: flex; align-items: flex-start; gap: 8px; font-size: 9.5px; color: #e2e8f0; margin-bottom: 10px; word-break: break-all; }
        .contact-icon { width: 20px; height: 20px; border-radius: 50%; background: #fff; color: #383e45; display: flex; align-items: center; justify-content: center; font-size: 9px; flex-shrink: 0; margin-top: 1px; }

        .social-strip { display: flex; justify-content: center; gap: 12px; font-size: 16px; margin-top: auto; padding-top: 12px; }

        /* Right Column (White) */
        .right-col { width: 62%; background: #fff; color: #000; padding: 32px 28px; display: flex; flex-direction: column; gap: 18px; }
        .header-name { font-family: 'Montserrat', sans-serif; font-size: 26px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; color: #111; margin-bottom: 2px; }
        .header-sub { font-size: 11.5px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: #94a3b8; margin-bottom: 12px; }

        .sec-frame { border: 1.5px solid #000; text-align: center; font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; padding: 4px 10px; margin-bottom: 12px; }

        .entry-block { margin-bottom: 12px; page-break-inside: avoid; break-inside: avoid; }
        .entry-row { display: grid; grid-template-columns: 80px 1fr; gap: 12px; }
        .entry-dates { font-size: 10px; font-weight: 800; color: #111; }
        .entry-title { font-family: 'Montserrat', sans-serif; font-size: 11.5px; font-weight: 800; color: #000; text-transform: uppercase; margin-bottom: 1px; }
        .entry-co { font-size: 10px; color: #64748b; font-weight: 600; margin-bottom: 2px; }
        .entry-desc { font-size: 9.5px; line-height: 1.5; color: #4b5563; }

        /* Skills Progress Bars */
        .skill-bar-row { display: grid; grid-template-columns: 90px 1fr; align-items: center; gap: 10px; margin-bottom: 6px; }
        .skill-bar-name { font-size: 9.5px; font-weight: 800; text-transform: uppercase; color: #111; }
        .skill-track { width: 100%; height: 10px; border: 1.5px solid #000; background: #fff; padding: 1px; }
        .skill-fill { height: 100%; background: #000; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="left-col">
        <div class="avatar-wrap">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
            @else
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'M', 0, 1)) }}</div>
            @endif
        </div>

        @if(!empty($summary))
        <div>
            <div class="badge-bar">PERSONAL</div>
            <div class="left-text">{{ $summary }}</div>
        </div>
        @endif

        <div>
            <div class="badge-bar">CONTACT</div>
            @if(!empty($candidate['phone']))
            <div class="contact-item"><div class="contact-icon">📞</div><div><strong>Cell No</strong><br>{{ $candidate['phone'] }}</div></div>
            @endif
            @if(!empty($candidate['email']))
            <div class="contact-item"><div class="contact-icon">✉️</div><div><strong>EMAIL</strong><br>{{ $candidate['email'] }}</div></div>
            @endif
            @if(!empty($candidate['website']))
            <div class="contact-item"><div class="contact-icon">🌐</div><div><strong>WEB</strong><br>{{ $candidate['website'] }}</div></div>
            @endif
            @if(!empty($candidate['location']))
            <div class="contact-item"><div class="contact-icon">📍</div><div><strong>ADDRES</strong><br>{{ $candidate['location'] }}</div></div>
            @endif
        </div>

        @if(!empty($references) && count($references) > 0)
        <div>
            <div class="badge-bar">REFERNCE</div>
            @foreach($references as $r)
            <div class="left-text" style="margin-bottom:6px;">
                <strong>{{ $r['name'] ?? '' }}</strong><br>
                {{ $r['designation'] ?? '' }} @if(!empty($r['organization'])) - {{ $r['organization'] }}@endif<br>
                @if(!empty($r['phone']))📞 {{ $r['phone'] }}@endif
            </div>
            @endforeach
        </div>
        @endif

        <div class="social-strip">
            <span>📷</span><span>🐦</span><span>💬</span>
        </div>
    </div>

    <div class="right-col">
        <div>
            <h1 class="header-name">{{ $candidate['full_name'] ?? 'MD BAKIBILLAH RAHAT' }}</h1>
            <div class="header-sub">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-frame">EDUCATION</div>
            @foreach($education as $ed)
            <div class="entry-block">
                <div class="entry-row">
                    <div class="entry-dates">@if(!empty($ed['start_date'])){{ $ed['start_date'] }}@if(!empty($ed['end_date']))-{{ $ed['end_date'] }}@endif @endif</div>
                    <div>
                        <div class="entry-title">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                        <div class="entry-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                        @if(!empty($ed['description']))<div class="entry-desc">{{ $ed['description'] }}</div>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-frame">EXPERIENCE</div>
            @foreach($experience as $e)
            <div class="entry-block">
                <div class="entry-row">
                    <div class="entry-dates">@if(!empty($e['start_date'])){{ $e['start_date'] }}<br>@if(!empty($e['end_date'])){{ $e['end_date'] }}@else Present @endif @endif</div>
                    <div>
                        <div class="entry-title">{{ $e['position'] ?? $e['title'] ?? '' }}</div>
                        <div class="entry-co">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) > {{ $e['location'] }}@endif</div>
                        @if(!empty($e['description']))<div class="entry-desc">{{ $e['description'] }}</div>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="sec-frame">SKILLS</div>
            @foreach($skills as $idx => $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; $pct = [85, 75, 80, 95][$idx % 4]; @endphp
            @if($sn)
            <div class="skill-bar-row">
                <div class="skill-bar-name">{{ $sn }}</div>
                <div class="skill-track"><div class="skill-fill" style="width: {{ $pct }}%;"></div></div>
            </div>
            @endif
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>