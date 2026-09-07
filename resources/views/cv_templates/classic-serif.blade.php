<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Classic Serif</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Lora', serif; background: #fff; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 36px 42px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 12px; }
        .header h1 { font-family: 'Cinzel', serif; font-size: 26px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
        .header .title { font-size: 11px; font-style: italic; margin-top: 2px; color: #444; }
        .contact-line { display: flex; justify-content: center; flex-wrap: wrap; gap: 14px; font-size: 9.5px; margin-top: 6px; color: #555; }
        .sec-title { font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; border-bottom: 1px solid #777; padding-bottom: 2px; margin-bottom: 8px; }
        .item-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; }
        .item-sub { font-size: 10px; font-style: italic; color: #444; margin-bottom: 3px; }
        .desc { font-size: 9.5px; line-height: 1.6; color: #333; }
        .desc ul { padding-left: 16px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
        @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        <div class="contact-line">
            @if(!empty($candidate['phone']))<span>{{ $candidate['phone'] }}</span> · @endif
            @if(!empty($candidate['email']))<span>{{ $candidate['email'] }}</span> · @endif
            @if(!empty($candidate['location']))<span>{{ $candidate['location'] }}</span>@endif
            @if(!empty($candidate['website'])) · <span>{{ $candidate['website'] }}</span>@endif
        </div>
    </div>

    @if(!empty($summary))
    <div><div class="sec-title">Professional Summary</div><p class="desc">{{ $summary }}</p></div>
    @endif

    @if(!empty($experience) && count($experience) > 0)
    <div>
        <div class="sec-title">Experience</div>
        @foreach($experience as $e)
        <div style="margin-bottom:10px;">
            <div class="item-head"><div>{{ $e['company'] ?? $e['company_name'] ?? '' }}</div><div style="font-weight:400;font-style:italic;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
            <div class="item-sub">{{ $e['position'] ?? $e['title'] ?? '' }}@if(!empty($e['location'])) — {{ $e['location'] }}@endif</div>
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
            <div class="item-head"><div>{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div><div style="font-weight:400;font-style:italic;">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div></div>
            <div class="item-sub">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
        </div>
        @endforeach
    </div>
    @endif

    @if(!empty($skills) && count($skills) > 0)
    <div>
        <div class="sec-title">Skills & Competencies</div>
        <p class="desc">
            @php $skillNames = []; foreach($skills as $s) { $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; if($sn) $skillNames[] = $sn; } @endphp
            {{ implode(' • ', $skillNames) }}
        </p>
    </div>
    @endif
</div>
</body>
</html>