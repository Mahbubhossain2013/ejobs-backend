<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Fashion Designer</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --slate-blue: #1e3a5f; --slate-dark: #0f1e33; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #f1f5f9; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .side { width: 33%; background: var(--slate-blue); color: #fff; padding: 28px 18px; display: flex; flex-direction: column; gap: 16px; }
        .main { flex: 1; padding: 28px 26px; display: flex; flex-direction: column; gap: 16px; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.4); margin: 0 auto 8px; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; background: var(--slate-dark); font-family: 'Cormorant Garamond', serif; }
        .side-title { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #93c5fd; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 3px; margin-bottom: 8px; }
        .main-head h1 { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 700; color: var(--slate-dark); letter-spacing: 1px; }
        .main-head .sub { font-size: 10px; color: #0284c7; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; }
        .sec-title { font-family: 'Cormorant Garamond', serif; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--slate-blue); border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; }
        .exp-co { font-size: 9.5px; color: #0284c7; font-weight: 600; margin-bottom: 2px; }
        .exp-desc { font-size: 9px; color: #475569; line-height: 1.5; }
        .exp-desc ul { padding-left: 14px; }
    
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
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'N', 0, 1)) }}</div>
                @endif
            </div>
            <div style="font-size:10px;color:#93c5fd;letter-spacing:1px;text-transform:uppercase;">Curriculum Vitae</div>
        </div>

        <div>
            <div class="side-title">Contact</div>
            @if(!empty($candidate['phone']))<div style="font-size:9px;margin-bottom:4px;">📱 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div style="font-size:9px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="font-size:9px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="side-title">Skills</div>
            @foreach($skills as $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
            @if($sn)<div style="font-size:9px;margin-bottom:3px;color:#e2e8f0;">• {{ $sn }}</div>@endif
            @endforeach
        </div>
        @endif
    </div>

    <div class="main">
        <div class="main-head">
            <h1>{{ $candidate['full_name'] ?? 'Nicole Matthews' }}</h1>
            <div class="sub">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($summary))
        <div><div class="sec-title">PROFILE</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">WORK EXPERIENCE</div>
            @foreach($experience as $e)
            <div class="exp-item">
                <div class="exp-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
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
        @endif

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-title">EDUCATION</div>
            @foreach($education as $ed)
            <div class="exp-item">
                <div class="exp-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $ed['start_date'] ?? '' }}</div></div>
                <div class="exp-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>