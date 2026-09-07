<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Infographic</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --indigo: #4338ca; --indigo-light: #e0e7ff; --text: #1e1b4b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #eef2ff; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .side { width: 36%; background: #312e81; color: #fff; padding: 26px 20px; display: flex; flex-direction: column; gap: 16px; }
        .main { flex: 1; padding: 26px 24px; display: flex; flex-direction: column; gap: 16px; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #818cf8; margin: 0 auto 10px; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--indigo); border-bottom: 2px solid var(--indigo-light); padding-bottom: 4px; margin-bottom: 8px; }
        .side-title { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #c7d2fe; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 3px; margin-bottom: 8px; }
        .bar-bg { width: 100%; height: 5px; background: rgba(255,255,255,0.2); border-radius: 3px; overflow: hidden; margin-top: 2px; }
        .bar-fill { height: 100%; background: #a5b4fc; }
        .card { margin-bottom: 10px; }
        .card-head { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; color: #1e1b4b; }
        .card-sub { font-size: 10px; color: var(--indigo); font-weight: 600; margin: 1px 0 3px; }
        .card-desc { font-size: 9.5px; color: #475569; line-height: 1.55; }
        .card-desc ul { padding-left: 14px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="side">
        <div style="text-align:center;">
            <div class="avatar">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
                @endif
            </div>
            <h1 style="font-size:18px;font-weight:800;">{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div style="font-size:10px;color:#c7d2fe;margin-top:2px;">{{ $candidate['title'] }}</div>@endif
        </div>

        <div>
            <div class="side-title">Contact</div>
            @if(!empty($candidate['phone']))<div style="font-size:9.5px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div style="font-size:9.5px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="font-size:9.5px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="side-title">Skills & Metrics</div>
            @foreach($skills as $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; $lvl = is_array($s) ? ($s['level'] ?? '') : null; $pct = ['expert'=>95,'advanced'=>85,'intermediate'=>65,'beginner'=>40][strtolower($lvl ?? '')] ?? 80; @endphp
            @if($sn)
            <div style="margin-bottom:6px;">
                <div style="display:flex;justify-content:space-between;font-size:9.5px;"><span>{{ $sn }}</span><span style="color:#c7d2fe">{{ $pct }}%</span></div>
                <div class="bar-bg"><div class="bar-fill" style="width:{{ $pct }}%"></div></div>
            </div>
            @endif
            @endforeach
        </div>
        @endif
    </div>

    <div class="main">
        @if(!empty($summary))
        <div><div class="sec-title">Profile</div><p style="font-size:10px;line-height:1.65;color:#334155;">{{ $summary }}</p></div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">Experience</div>
            @foreach($experience as $e)
            <div class="card">
                <div class="card-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:9px;color:#64748b;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                <div class="card-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
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

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-title">Education</div>
            @foreach($education as $ed)
            <div class="card">
                <div class="card-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:9px;color:#64748b;">{{ $ed['start_date'] ?? '' }}</div></div>
                <div class="card-sub">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>