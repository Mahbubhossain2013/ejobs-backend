<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Rose Creative</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --rose: #f43f5e; --rose-dark: #be123c; --rose-bg: #fff1f2; --text: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fff5f5; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 32px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .header { display: flex; align-items: center; gap: 20px; border-bottom: 2px dashed #fecdd3; padding-bottom: 16px; }
        .avatar { width: 76px; height: 76px; border-radius: 50%; border: 3px solid var(--rose); overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; color: var(--rose); background: var(--rose-bg); }
        .header h1 { font-family: 'Quicksand', sans-serif; font-size: 26px; font-weight: 700; color: var(--rose-dark); }
        .header .title { font-size: 11px; color: var(--rose); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .contact-strip { display: flex; flex-wrap: wrap; gap: 12px; font-size: 9.5px; color: #64748b; margin-top: 6px; }
        .sec-title { font-family: 'Quicksand', sans-serif; font-size: 12px; font-weight: 700; color: var(--rose-dark); text-transform: uppercase; border-left: 4px solid var(--rose); padding-left: 6px; margin-bottom: 8px; }
        .exp-box { background: var(--rose-bg); border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; color: var(--rose-dark); }
        .exp-co { font-size: 10px; color: var(--rose); font-weight: 600; margin-bottom: 2px; }
        .exp-desc { font-size: 9.5px; color: #475569; line-height: 1.55; }
        .exp-desc ul { padding-left: 14px; }
        .tag { display: inline-block; font-size: 9px; padding: 2px 8px; border-radius: 12px; background: var(--rose-bg); color: var(--rose-dark); border: 1px solid #fecdd3; font-weight: 600; margin: 0 3px 4px 0; }
    
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
        <div>
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
            <div class="contact-strip">
                @if(!empty($candidate['phone']))<span>📱 {{ $candidate['phone'] }}</span>@endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span>@endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
            </div>
        </div>
    </div>

    @if(!empty($summary))
    <div><div class="sec-title">About Me</div><p style="font-size:10px;line-height:1.65;color:#475569;">{{ $summary }}</p></div>
    @endif

    @if(!empty($experience) && count($experience) > 0)
    <div>
        <div class="sec-title">Experience</div>
        @foreach($experience as $e)
        <div class="exp-box">
            <div class="exp-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:9px;color:#881337;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
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

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-title">Education</div>
            @foreach($education as $ed)
            <div style="margin-bottom:8px;">
                <div style="font-size:11px;font-weight:700;color:var(--rose-dark);">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                <div style="font-size:10px;color:var(--rose);">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                <div style="font-size:9px;color:#64748b;">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div>
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
                @if($sn)<span class="tag">{{ $sn }}</span>@endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</body>
</html>