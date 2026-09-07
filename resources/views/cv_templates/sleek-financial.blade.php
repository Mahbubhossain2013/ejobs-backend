<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Sleek Financial</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --yellow: #eab308; --yellow-dark: #ca8a04; --yellow-light: #fef9c3; --dark: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fafafa; color: var(--dark); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 32px 36px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #fef08a; padding-bottom: 18px; }
        .badge-title { background: #000; color: #fff; padding: 4px 14px; border-radius: 20px; font-size: 10px; font-weight: 700; display: inline-block; margin-bottom: 6px; letter-spacing: 0.5px; }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 700; color: #0f172a; line-height: 1.1; }
        .header .subtitle { font-size: 11px; color: var(--yellow-dark); font-weight: 600; margin-top: 3px; }
        .avatar-box { width: 86px; height: 86px; border-radius: 50%; background: var(--yellow); padding: 4px; box-shadow: 0 8px 20px rgba(234,179,8,0.3); overflow: hidden; flex-shrink: 0; }
        .avatar-box img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; border-radius: 50%; background: #0f172a; display: flex; align-items: center; justify-content: center; color: var(--yellow); font-size: 30px; font-weight: 700; }
        .contact-strip { display: flex; flex-wrap: wrap; gap: 8px 18px; font-size: 9.5px; color: var(--muted); margin-top: 8px; }
        .contact-strip a { color: var(--yellow-dark); text-decoration: none; }
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .sec-title { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; margin-bottom: 10px; }
        .sec-icon { width: 22px; height: 22px; border-radius: 50%; background: var(--yellow-light); color: var(--yellow-dark); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }
        .card { background: #fafafa; border: 1px solid #f1f5f9; border-left: 3px solid var(--yellow); padding: 8px 12px; border-radius: 4px; margin-bottom: 8px; }
        .card-row { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #0f172a; }
        .card-sub { font-size: 9.5px; color: var(--yellow-dark); font-weight: 600; margin: 1px 0 3px; }
        .card-desc { font-size: 9px; color: #475569; line-height: 1.5; }
        .card-desc ul { padding-left: 14px; }
        .pill { display: inline-block; font-size: 9px; padding: 3px 9px; background: var(--yellow-light); color: var(--yellow-dark); font-weight: 700; border-radius: 12px; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <div>
            <div class="badge-title">Executive Bio</div>
            <h1>{{ $candidate['full_name'] ?? 'Joshua Freedman' }}</h1>
            <div class="subtitle">{{ $candidate['title'] ?? '' }}</div>
            <div class="contact-strip">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span>@endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span>@endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
                @if(!empty($candidate['website']))<span>🌐 <a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
            </div>
        </div>
        <div class="avatar-box">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="">
            @else
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'J', 0, 1)) }}</div>
            @endif
        </div>
    </div>

    @if(!empty($summary))
    <div>
        <div class="sec-title"><div class="sec-icon">★</div>Profile & Objectives</div>
        <p style="font-size:10px;line-height:1.65;color:#334155;">{{ $summary }}</p>
    </div>
    @endif

    <div class="grid-layout">
        <div>
            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-title"><div class="sec-icon">💼</div>Work Experience</div>
                @foreach($experience as $e)
                <div class="card">
                    <div class="card-row"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                    <div class="card-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>
                    @if(!empty($e['description']))
                    <div class="card-desc">
                        @if(preg_match('/<[^>]+>/', $e['description'])) {!! $e['description'] !!} @else
                        @php $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $e['description']))); @endphp
                        @if(count($lines) > 1)<ul>@foreach($lines as $l)<li>{{ $l }}</li>@endforeach</ul>@else<p>{{ $e['description'] }}</p>@endif
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($projects) && count($projects) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title"><div class="sec-icon">📁</div>Key Projects</div>
                @foreach($projects as $p)
                <div class="card">
                    <div class="card-row"><div>{{ $p['name'] ?? $p['title'] ?? '' }}</div></div>
                    @if(!empty($p['description']))<div class="card-desc">{{ $p['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div>
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-title"><div class="sec-icon">🎓</div>Education</div>
                @foreach($education as $ed)
                <div class="card" style="border-left-color:#0f172a;">
                    <div class="card-row"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div></div>
                    <div class="card-sub" style="color:#0f172a;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title"><div class="sec-icon">⚡</div>Skills & Expertise</div>
                <div>
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span class="pill">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title"><div class="sec-icon">🌐</div>Languages</div>
                @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                <div style="display:flex;justify-content:space-between;font-size:9.5px;margin-bottom:4px;border-bottom:1px dashed #e2e8f0;padding-bottom:2px;"><span>{{ $ln }}</span><span style="color:var(--yellow-dark);font-weight:700;">{{ $lp }}</span></div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>