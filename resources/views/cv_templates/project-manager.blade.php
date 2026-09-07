<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Project Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,600&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #172554; --navy-dark: #0f172a; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f172a; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .side { width: 34%; background: var(--navy); color: #fff; padding: 26px 18px; display: flex; flex-direction: column; gap: 16px; }
        .main { flex: 1; padding: 26px 26px; display: flex; flex-direction: column; gap: 16px; }
        .avatar { width: 78px; height: 78px; border-radius: 8px; border: 2px solid #60a5fa; overflow: hidden; margin-bottom: 8px; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #fff; background: #1e3a8a; }
        .about-title { font-family: 'Playfair Display', serif; font-style: italic; font-size: 22px; color: #93c5fd; }
        .side-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #93c5fd; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 3px; margin-bottom: 8px; }
        .main-head h1 { font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .main-head .sub { font-size: 11px; color: #2563eb; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; border-bottom: 2px solid #2563eb; padding-bottom: 3px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 8px; padding-left: 10px; border-left: 2px solid #93c5fd; }
        .exp-row { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #0f172a; }
        .exp-co { font-size: 9.5px; color: #2563eb; font-weight: 600; margin: 1px 0 2px; }
        .exp-desc { font-size: 9px; color: #475569; line-height: 1.5; }
        .exp-desc ul { padding-left: 14px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="side">
        <div>
            <div class="avatar">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'M', 0, 1)) }}</div>
                @endif
            </div>
            <div class="about-title">About me</div>
            @if(!empty($summary))<p style="font-size:9px;line-height:1.55;color:#bfdbfe;margin-top:6px;">{{ $summary }}</p>@endif
        </div>

        <div>
            <div class="side-title">Contact</div>
            @if(!empty($candidate['phone']))<div style="font-size:9.5px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div style="font-size:9.5px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="font-size:9.5px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
            @if(!empty($candidate['website']))<div style="font-size:9.5px;margin-bottom:4px;">🌐 <a href="{{ $candidate['website'] }}" style="color:#93c5fd;">{{ $candidate['website'] }}</a></div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="side-title">Skills Matrix</div>
            @foreach($skills as $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
            @if($sn)<div style="font-size:9px;padding:2px 0;border-bottom:1px dashed rgba(255,255,255,0.15)">{{ $sn }}</div>@endif
            @endforeach
        </div>
        @endif
    </div>

    <div class="main">
        <div class="main-head">
            <h1>{{ $candidate['full_name'] ?? 'MICHAEL LYNCH' }}</h1>
            <div class="sub">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">Work Experience</div>
            @foreach($experience as $e)
            <div class="exp-item">
                <div class="exp-row"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
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

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-title">Education</div>
            @foreach($education as $ed)
            <div class="exp-item">
                <div class="exp-row"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $ed['start_date'] ?? '' }}</div></div>
                <div class="exp-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>