<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Red Executive</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Lato:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --red: #dc2626; --red-dark: #991b1b; --dark: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Lato', sans-serif; background: #f8fafc; color: var(--dark); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 32px 36px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .top-bar { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid var(--red); padding-bottom: 14px; }
        .top-bar h1 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; }
        .top-bar .title { font-size: 12px; color: var(--red); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-top: 2px; }
        .contact-grid { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 9.5px; color: var(--muted); margin-top: 6px; }
        .contact-grid a { color: var(--red); text-decoration: none; }
        .sec-title { font-family: 'Playfair Display', serif; font-size: 13px; font-weight: 700; color: var(--red-dark); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #fee2e2; padding-bottom: 4px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 10px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; }
        .exp-co { font-size: 10px; color: var(--red); font-style: italic; margin-bottom: 2px; }
        .exp-desc { font-size: 9.5px; color: #475569; line-height: 1.55; }
        .exp-desc ul { padding-left: 14px; }
        .skills-wrap { display: flex; flex-wrap: wrap; gap: 6px; }
        .skill-box { font-size: 9px; padding: 2px 8px; border: 1px solid #fca5a5; background: #fef2f2; color: var(--red-dark); border-radius: 3px; font-weight: 600; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="top-bar">
        <div>
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
            <div class="contact-grid">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span>@endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span>@endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
                @if(!empty($candidate['website']))<span>🌐 <a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
            </div>
        </div>
        @if(!empty($candidate['photo_url']))
        <img src="{{ $candidate['photo_url'] }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--red);" alt="">
        @endif
    </div>

    @if(!empty($summary))
    <div>
        <div class="sec-title">Executive Summary</div>
        <p style="font-size:10px;line-height:1.65;color:#334155;">{{ $summary }}</p>
    </div>
    @endif

    @if(!empty($experience) && count($experience) > 0)
    <div>
        <div class="sec-title">Professional Experience</div>
        @foreach($experience as $e)
        <div class="exp-item">
            <div class="exp-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:9.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
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

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-title">Education</div>
            @foreach($education as $ed)
            <div style="margin-bottom:8px;">
                <div style="font-size:11px;font-weight:700;">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                <div style="font-size:10px;color:var(--red);">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                <div style="font-size:9px;color:var(--muted);">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="sec-title">Core Competencies</div>
            <div class="skills-wrap">
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                @if($sn)<span class="skill-box">{{ $sn }}</span>@endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</body>
</html>