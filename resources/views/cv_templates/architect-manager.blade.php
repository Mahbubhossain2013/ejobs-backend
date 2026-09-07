<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Architect Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --burgundy: #991b1b; --burgundy-light: #fef2f2; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #f8fafc; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .top-banner { background: var(--burgundy); color: #fff; padding: 22px 30px; text-align: center; }
        .top-banner h1 { font-family: 'Cinzel', serif; font-size: 24px; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; }
        .top-banner .sub { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.9; margin-top: 2px; }
        .contact-bar { background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: center; flex-wrap: wrap; gap: 14px; padding: 8px 20px; font-size: 9px; color: var(--muted); }
        .contact-bar a { color: var(--burgundy); }
        .body-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding: 24px 30px; flex: 1; }
        .sec-title { font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; color: var(--burgundy); letter-spacing: 1.5px; text-transform: uppercase; border-bottom: 1.5px solid var(--burgundy); padding-bottom: 3px; margin-bottom: 10px; }
        .exp-item { margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; }
        .exp-co { font-size: 9.5px; color: var(--burgundy); font-weight: 600; margin: 1px 0 3px; }
        .exp-desc { font-size: 9px; color: #475569; line-height: 1.55; }
        .exp-desc ul { padding-left: 14px; }
        .tag { display: inline-block; font-size: 8.5px; padding: 2px 7px; background: var(--burgundy-light); color: var(--burgundy); font-weight: 600; border-radius: 3px; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="top-banner">
        <h1>{{ $candidate['full_name'] ?? 'ADAM CARTER' }}</h1>
        <div class="sub">{{ $candidate['title'] ?? '' }}</div>
    </div>
    <div class="contact-bar">
        @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span>@endif
        @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span>@endif
        @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
        @if(!empty($candidate['website']))<span>🌐 <a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
    </div>
    <div class="body-grid">
        <div>
            @if(!empty($summary))
            <div><div class="sec-title">PROFILE</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div style="margin-top:12px;">
                <div class="sec-title">WORK EXPERIENCE</div>
                @foreach($experience as $e)
                <div class="exp-item">
                    <div class="exp-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                    <div class="exp-co">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>
                    @if(!empty($e['description']))
                    <div class="exp-desc">
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
        </div>

        <div>
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-title">EDUCATION</div>
                @foreach($education as $ed)
                <div class="exp-item">
                    <div class="exp-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div></div>
                    <div class="exp-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:12px;">
                <div class="sec-title">SKILLS & EXPERTISE</div>
                <div>
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span class="tag">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($projects) && count($projects) > 0)
            <div style="margin-top:12px;">
                <div class="sec-title">PROJECTS</div>
                @foreach($projects as $p)
                <div style="margin-bottom:6px;">
                    <div style="font-size:10.5px;font-weight:700;">{{ $p['name'] ?? $p['title'] ?? '' }}</div>
                    @if(!empty($p['description']))<div style="font-size:9px;color:#475569;">{{ $p['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>