<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Golden Luxury</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #d4af37; --gold-dark: #aa820a; --gold-bg: #fffbf0; --black: #111; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #faf8f5; color: #222; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 34px 38px; display: flex; flex-direction: column; gap: 16px; border: 4px double var(--gold);     align-items: stretch;
        }
        @media print { .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .head { text-align: center; border-bottom: 1px solid var(--gold); padding-bottom: 14px; }
        .head h1 { font-family: 'Cinzel', serif; font-size: 26px; font-weight: 800; letter-spacing: 2px; color: #111; }
        .head .title { font-size: 11px; color: var(--gold-dark); text-transform: uppercase; letter-spacing: 2px; margin-top: 2px; font-weight: 600; }
        .contact { display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; font-size: 9.5px; color: #555; margin-top: 6px; }
        .sec-title { font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; color: var(--gold-dark); text-transform: uppercase; letter-spacing: 1.5px; border-bottom: 1px solid var(--gold); padding-bottom: 3px; margin-bottom: 8px; }
        .item-head { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; }
        .item-co { font-size: 10px; color: var(--gold-dark); font-weight: 600; margin-bottom: 3px; }
        .desc { font-size: 9.5px; line-height: 1.55; color: #444; }
        .desc ul { padding-left: 14px; }
        .pill { display: inline-block; font-size: 9px; padding: 2px 8px; border-radius: 4px; background: var(--gold-bg); border: 1px solid #fde68a; color: var(--gold-dark); font-weight: 600; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="head">
        <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
        @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        <div class="contact">
            @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span> · @endif
            @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span> · @endif
            @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
        </div>
    </div>

    @if(!empty($summary))
    <div><div class="sec-title">Executive Summary</div><p class="desc">{{ $summary }}</p></div>
    @endif

    @if(!empty($experience) && count($experience) > 0)
    <div>
        <div class="sec-title">Experience</div>
        @foreach($experience as $e)
        <div style="margin-bottom:10px;">
            <div class="item-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:9px;color:#777;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
            <div class="item-co">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
            @if(!empty($e['description']))
            <div class="desc">
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

    @if(!empty($education) && count($education) > 0)
    <div>
        <div class="sec-title">Education</div>
        @foreach($education as $ed)
        <div style="margin-bottom:8px;">
            <div class="item-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:9px;color:#777;">{{ $ed['start_date'] ?? '' }}</div></div>
            <div class="item-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
        </div>
        @endforeach
    </div>
    @endif

    @if(!empty($skills) && count($skills) > 0)
    <div>
        <div class="sec-title">Key Skills</div>
        <div>@foreach($skills as $s)@php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp@if($sn)<span class="pill">{{ $sn }}</span>@endif@endforeach</div>
    </div>
    @endif
</div>
</body>
</html>