<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Purple Gradient</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --p-grad: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #c026d3 100%); --p-primary: #7c3aed; --p-soft: #f5f3ff; --p-border: #e9d5ff; --text: #1e1b4b; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .header { background: var(--p-grad); color: #fff; padding: 28px 32px; display: flex; align-items: center; gap: 24px; position: relative; overflow: hidden; }
        .header::after { content: ''; position: absolute; right: -40px; top: -40px; width: 160px; height: 160px; border-radius: 50%; background: rgba(255,255,255,0.1); }
        .avatar { width: 84px; height: 84px; border-radius: 20px; border: 3px solid rgba(255,255,255,0.6); overflow: hidden; flex-shrink: 0; background: rgba(255,255,255,0.2); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-size: 30px; font-weight: 800; color: #fff; }
        .header-content h1 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }
        .header-content .title { font-size: 11px; opacity: 0.9; margin-top: 2px; font-weight: 500; }
        .contact-strip { display: flex; flex-wrap: wrap; gap: 10px 18px; margin-top: 10px; font-size: 9.5px; opacity: 0.9; }
        .contact-strip a { color: #fff; text-decoration: underline; }
        .body-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; padding: 24px 30px; flex: 1; }
        .sec-title { font-family: 'Outfit', sans-serif; font-size: 12px; font-weight: 700; color: var(--p-primary); text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid var(--p-border); padding-bottom: 4px; margin-bottom: 10px; }
        .card { background: #fff; border-left: 3px solid var(--p-primary); padding: 8px 12px; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); background: #faf5ff; border-radius: 0 8px 8px 0; }
        .card-row { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; color: #1e1b4b; }
        .card-sub { font-size: 10px; color: var(--p-primary); font-weight: 600; margin: 2px 0 4px; }
        .card-date { font-size: 9px; color: var(--text-muted); font-weight: 500; }
        .card-desc { font-size: 9.5px; color: #4b5563; line-height: 1.55; }
        .card-desc ul { padding-left: 14px; }
        .tag-wrap { display: flex; flex-wrap: wrap; gap: 5px; }
        .tag { font-size: 9px; padding: 3px 9px; border-radius: 12px; background: var(--p-soft); color: var(--p-primary); font-weight: 600; border: 1px solid var(--p-border); }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <div class="avatar">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="">
            @else
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
            @endif
        </div>
        <div class="header-content">
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
            <div class="contact-strip">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span>@endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span>@endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
                @if(!empty($candidate['website']))<span>🌐 <a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
            </div>
        </div>
    </div>

    <div class="body-grid">
        <div>
            @if(!empty($summary))
            <div>
                <div class="sec-title">About Me</div>
                <div class="card"><p style="font-size:10px;line-height:1.65;color:#374151;">{{ $summary }}</p></div>
            </div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-title">Work Experience</div>
                @foreach($experience as $e)
                <div class="card">
                    <div class="card-row"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div class="card-date">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
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
            <div>
                <div class="sec-title">Projects</div>
                @foreach($projects as $p)
                <div class="card">
                    <div class="card-row"><div>{{ $p['name'] ?? $p['title'] ?? '' }}</div>@if(!empty($p['url']))<a href="{{ $p['url'] }}" style="font-size:9px;color:var(--p-primary);">Link</a>@endif</div>
                    @if(!empty($p['description']))<div class="card-desc" style="margin-top:3px;">{{ $p['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div style="display:flex;flex-direction:column;gap:14px;">
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-title">Education</div>
                @foreach($education as $ed)
                <div style="margin-bottom:8px;padding-bottom:6px;border-bottom:1px dashed var(--p-border);">
                    <div style="font-size:11px;font-weight:700;color:var(--text);">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                    <div style="font-size:10px;color:var(--p-primary);font-weight:600;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                    <div style="font-size:9px;color:var(--text-muted);">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="sec-title">Skills</div>
                <div class="tag-wrap">
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span class="tag">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div>
                <div class="sec-title">Languages</div>
                @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                <div style="display:flex;justify-content:space-between;font-size:9.5px;margin-bottom:4px;"><strong>{{ $ln }}</strong><span style="color:var(--p-primary)">{{ $lp }}</span></div>
                @endforeach
            </div>
            @endif

            @if(!empty($hobbies) && count($hobbies) > 0)
            <div>
                <div class="sec-title">Hobbies</div>
                <div class="tag-wrap">
                    @foreach($hobbies as $h)<span class="tag" style="background:#ede9fe;">{{ is_array($h) ? ($h['name'] ?? $h['title'] ?? '') : $h }}</span>@endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>