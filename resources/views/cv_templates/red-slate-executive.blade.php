<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Red Slate Executive</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Dancing+Script:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #e2e8f0; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; height: auto; margin: 0 auto; background: #fff; padding: 32px 36px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; height: auto; page-break-after: auto; } }

        .header-grid { display: grid; grid-template-columns: 90px 1fr 180px; gap: 20px; align-items: center; border-bottom: 2px solid #ef4444; padding-bottom: 16px; }
        .avatar-ring { width: 85px; height: 85px; border-radius: 50%; border: 3px solid #ef4444; overflow: hidden; background: #1c1917; }
        .avatar-ring img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 800; color: #ef4444; }

        .header-name { font-family: 'Montserrat', sans-serif; font-size: 26px; font-weight: 900; letter-spacing: 1px; color: #111; }
        .header-sub { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
        .about-pill { background: #ef4444; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 9.5px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; padding: 3px 12px; display: inline-block; }

        .contact-right { font-size: 9px; color: #475569; display: flex; flex-direction: column; gap: 4px; text-align: right; }

        .bar-red { background: #ef4444; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; padding: 5px 12px; margin-bottom: 8px; }
        .bar-slate { background: #334155; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; padding: 5px 12px; margin-bottom: 8px; }

        .mid-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .edu-item { margin-bottom: 6px; font-size: 9.5px; line-height: 1.4; color: #334155; }
        .edu-item strong { color: #000; font-size: 10px; }

        .skill-bar-red { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-bottom: 6px; }
        .skill-fill-red { height: 100%; background: #ef4444; }

        .exp-timeline { border-left: 2px solid #e2e8f0; margin-left: 100px; padding-left: 16px; }
        .exp-item-block { position: relative; margin-bottom: 14px; page-break-inside: avoid; break-inside: avoid; }
        .exp-co-label { position: absolute; left: -116px; top: 0; width: 90px; text-align: right; font-size: 9.5px; font-weight: 800; color: #111; }
        .exp-co-label::after { content: ''; position: absolute; right: -21px; top: 4px; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; }

        .exp-pos { font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 800; color: #111; text-transform: uppercase; }
        .exp-desc { font-size: 9.5px; line-height: 1.5; color: #475569; }

        .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: end; margin-top: auto; padding-top: 10px; border-top: 2px solid #334155; }
        .signature-box { text-align: right; font-family: 'Dancing Script', cursive; font-size: 24px; color: #111; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header-grid">
        <div class="avatar-ring">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
            @else
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'J', 0, 1)) }}</div>
            @endif
        </div>
        <div>
            <h1 class="header-name">{{ $candidate['full_name'] ?? 'JOHN DOE' }}</h1>
            <div class="header-sub">{{ $candidate['title'] ?? '' }}</div>
            <div class="about-pill">ABOUT ME</div>
        </div>
        <div class="contact-right">
            @if(!empty($candidate['phone']))<div>📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div>✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div>📍 {{ $candidate['location'] }}</div>@endif
            @if(!empty($candidate['website']))<div>🌐 {{ $candidate['website'] }}</div>@endif
        </div>
    </div>

    @if(!empty($summary))
    <div style="font-size:10px;line-height:1.6;color:#475569;">
        {{ $summary }}
    </div>
    @endif

    <div class="mid-grid">
        <div>
            <div class="bar-red">FOLLOW ME</div>
            <div style="font-size:9.5px;color:#334155;line-height:1.6;">
                @if(!empty($candidate['linkedin']))<div><strong>L</strong> · {{ $candidate['linkedin'] }}</div>@endif
                @if(!empty($candidate['github']))<div><strong>G</strong> · {{ $candidate['github'] }}</div>@endif
                @if(!empty($candidate['website']))<div><strong>W</strong> · {{ $candidate['website'] }}</div>@endif
            </div>
        </div>

        <div>
            <div class="bar-slate">EDUCATION</div>
            @if(!empty($education) && count($education) > 0)
                @foreach($education as $ed)
                <div class="edu-item">
                    <strong>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</strong><br>
                    {{ $ed['institution'] ?? $ed['school'] ?? '' }} {{ !empty($ed['start_date']) ? '(' . $ed['start_date'] . (!empty($ed['end_date']) ? '-' . $ed['end_date'] : '') . ')' : '' }}
                </div>
                @endforeach
            @endif
        </div>

        <div>
            <div class="bar-red">SKILLS</div>
            @if(!empty($skills) && count($skills) > 0)
                @foreach($skills as $idx => $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; $pct = [90, 80, 85, 75][$idx % 4]; @endphp
                @if($sn)
                <div style="display:flex;justify-content:space-between;font-size:9px;font-weight:700;margin-bottom:2px;"><span>{{ $sn }}</span><span>{{ $pct }}%</span></div>
                <div class="skill-bar-red"><div class="skill-fill-red" style="width: {{ $pct }}%;"></div></div>
                @endif
                @endforeach
            @endif
        </div>
    </div>

    @if(!empty($experience) && count($experience) > 0)
    <div>
        <div class="bar-slate">WORK EXPERIENCE</div>
        <div class="exp-timeline">
            @foreach($experience as $e)
            <div class="exp-item-block">
                <div class="exp-co-label">{{ $e['company'] ?? '' }}<br><span style="font-size:8.5px;color:#64748b;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? '-' . $e['end_date'] : '-Present' }}</span></div>
                <div class="exp-pos">{{ $e['position'] ?? $e['title'] ?? '' }}</div>
                @if(!empty($e['description']))<div class="exp-desc">{{ $e['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bottom-grid">
        <div>
            <div class="bar-red">REFERENCE</div>
            @if(!empty($references) && count($references) > 0)
                @foreach($references as $r)
                <div style="font-size:9.5px;color:#334155;">
                    <strong>{{ $r['name'] ?? '' }}</strong> - {{ $r['designation'] ?? '' }} @if(!empty($r['phone']))(📞 {{ $r['phone'] }})@endif
                </div>
                @endforeach
            
            @endif
        </div>

        <div class="signature-box">
            <div style="font-size:10px;font-family:'Montserrat',sans-serif;font-weight:700;color:#64748b;text-transform:uppercase;">Signed</div>
            <div>{{ $candidate['full_name'] ?? 'John Doe' }}</div>
        </div>
    </div>
</div>
</body>
</html>