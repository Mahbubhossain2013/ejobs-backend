<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Interior Design</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,600;1,700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --green: #1e3a2f; --green-accent: #2e604d; --green-light: #e8f0eb; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #e8f0eb; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .side { width: 34%; background: var(--green); color: #fff; padding: 26px 18px; display: flex; flex-direction: column; gap: 16px; }
        .main { flex: 1; padding: 26px 26px; display: flex; flex-direction: column; gap: 16px; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #6ee7b7; overflow: hidden; margin: 0 auto 10px; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; background: var(--green-accent); font-family: 'Playfair Display', serif; }
        .script-name { font-family: 'Playfair Display', serif; font-style: italic; font-size: 26px; color: var(--green); }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--green); border-bottom: 2px solid var(--green-light); padding-bottom: 3px; margin-bottom: 8px; }
        .side-title { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #a7f3d0; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 3px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #0f172a; }
        .exp-co { font-size: 9.5px; color: var(--green-accent); font-weight: 600; margin-bottom: 2px; }
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
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'M', 0, 1)) }}</div>
                @endif
            </div>
            <div style="font-size:10px;color:#a7f3d0;text-transform:uppercase;letter-spacing:1px;">Interior Designer</div>
        </div>

        <div>
            <div class="side-title">Contact</div>
            @if(!empty($candidate['phone']))<div style="font-size:9px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div style="font-size:9px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="font-size:9px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="side-title">Skills & Design</div>
            @foreach($skills as $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
            @if($sn)<div style="font-size:9px;margin-bottom:3px;color:#d1fae5;">🌿 {{ $sn }}</div>@endif
            @endforeach
        </div>
        @endif
    </div>

    <div class="main">
        <div>
            <div class="script-name">{{ $candidate['full_name'] ?? 'Madison Bennet' }}</div>
            <div style="font-size:10.5px;color:var(--green-accent);font-weight:600;text-transform:uppercase;letter-spacing:1px;">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($summary))
        <div><div class="sec-title">About</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">Experience</div>
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
            <div class="sec-title">Education</div>
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