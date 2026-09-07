<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Minimal White</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fff; color: #111827; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 36px 40px; display: flex; flex-direction: column; gap: 20px;     align-items: stretch;
        }
        @media print { .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .head { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid #e5e7eb; padding-bottom: 16px; }
        .head h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.5px; }
        .head .title { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .contact-row { display: flex; flex-wrap: wrap; gap: 14px; font-size: 9.5px; color: #6b7280; }
        .contact-row a { color: #111827; }
        .sec-title { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #9ca3af; margin-bottom: 10px; }
        .exp-item { display: grid; grid-template-columns: 140px 1fr; gap: 16px; margin-bottom: 12px; }
        .exp-date { font-size: 9.5px; color: #9ca3af; }
        .exp-title { font-size: 11.5px; font-weight: 600; color: #111827; }
        .exp-co { font-size: 10px; color: #4b5563; margin-bottom: 4px; }
        .exp-desc { font-size: 9.5px; color: #6b7280; line-height: 1.6; }
        .exp-desc ul { padding-left: 14px; }
        .skill-tag { display: inline-block; font-size: 9px; padding: 2px 8px; border: 1px solid #e5e7eb; border-radius: 4px; color: #374151; margin: 0 4px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="head">
        <div>
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        </div>
        <div class="contact-row">
            @if(!empty($candidate['email']))<span>{{ $candidate['email'] }}</span>@endif
            @if(!empty($candidate['phone']))<span>{{ $candidate['phone'] }}</span>@endif
            @if(!empty($candidate['location']))<span>{{ $candidate['location'] }}</span>@endif
            @if(!empty($candidate['website']))<span><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
        </div>
    </div>

    @if(!empty($summary))
    <div>
        <div class="sec-title">About</div>
        <p style="font-size:10px;line-height:1.7;color:#4b5563;">{{ $summary }}</p>
    </div>
    @endif

    @if(!empty($experience) && count($experience) > 0)
    <div>
        <div class="sec-title">Experience</div>
        @foreach($experience as $e)
        <div class="exp-item">
            <div class="exp-date">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div>
            <div>
                <div class="exp-title">{{ $e['position'] ?? $e['title'] ?? '' }}</div>
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
        </div>
        @endforeach
    </div>
    @endif

    @if(!empty($education) && count($education) > 0)
    <div>
        <div class="sec-title">Education</div>
        @foreach($education as $ed)
        <div class="exp-item">
            <div class="exp-date">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div>
            <div>
                <div class="exp-title">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                <div class="exp-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if(!empty($skills) && count($skills) > 0)
    <div>
        <div class="sec-title">Skills</div>
        <div>
            @foreach($skills as $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
            @if($sn)<span class="skill-tag">{{ $sn }}</span>@endif
            @endforeach
        </div>
    </div>
    @endif
</div>
</body>
</html>