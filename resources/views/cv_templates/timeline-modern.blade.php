<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Timeline Modern</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --cyan: #0891b2; --cyan-dark: #0e7490; --cyan-light: #ecfeff; --text: #0f172a; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 30px 34px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--cyan); padding-bottom: 14px; }
        .header h1 { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: var(--cyan-dark); }
        .header .title { font-size: 11px; color: var(--cyan); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .timeline { position: relative; padding-left: 20px; border-left: 2px solid var(--cyan); margin-left: 6px; }
        .timeline-node { position: absolute; left: -26px; width: 10px; height: 10px; border-radius: 50%; background: var(--cyan); border: 2px solid #fff; }
        .timeline-item { position: relative; margin-bottom: 12px; }
        .sec-title { font-family: 'Outfit', sans-serif; font-size: 12px; font-weight: 700; color: var(--cyan-dark); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; }
        .exp-co { font-size: 10px; color: var(--cyan); font-weight: 600; margin: 1px 0 3px; }
        .exp-desc { font-size: 9.5px; color: #475569; line-height: 1.55; }
        .exp-desc ul { padding-left: 14px; }
        .tag { display: inline-block; font-size: 9px; padding: 2px 8px; border-radius: 12px; background: var(--cyan-light); color: var(--cyan-dark); border: 1px solid #a5f3fc; font-weight: 600; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <div>
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        </div>
        <div style="font-size:9.5px;color:#64748b;text-align:right;">
            @if(!empty($candidate['phone']))<div>📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div>✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div>📍 {{ $candidate['location'] }}</div>@endif
        </div>
    </div>

    @if(!empty($summary))
    <div><div class="sec-title">Career Objective</div><p style="font-size:10px;line-height:1.65;color:#334155;">{{ $summary }}</p></div>
    @endif

    @if(!empty($experience) && count($experience) > 0)
    <div>
        <div class="sec-title">Experience Timeline</div>
        <div class="timeline">
            @foreach($experience as $e)
            <div class="timeline-item">
                <div class="timeline-node"></div>
                <div class="exp-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:9px;color:#64748b;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                <div class="exp-co">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
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
    </div>
    @endif

    @if(!empty($education) && count($education) > 0)
    <div>
        <div class="sec-title">Education</div>
        <div class="timeline">
            @foreach($education as $ed)
            <div class="timeline-item">
                <div class="timeline-node" style="background:#0e7490;"></div>
                <div class="exp-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:9px;color:#64748b;">{{ $ed['start_date'] ?? '' }}</div></div>
                <div class="exp-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(!empty($skills) && count($skills) > 0)
    <div>
        <div class="sec-title">Skills</div>
        <div>@foreach($skills as $s)@php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp@if($sn)<span class="tag">{{ $sn }}</span>@endif@endforeach</div>
    </div>
    @endif
</div>
</body>
</html>