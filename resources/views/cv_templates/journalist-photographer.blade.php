<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Journalist</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --olive: #4d7c0f; --olive-dark: #365314; --ochre: #d97706; --bg-warm: #fefce8; --text: #1c1917; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fefce8; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .top-hero { background: var(--ochre); color: #fff; padding: 22px 30px; display: flex; justify-content: space-between; align-items: center; }
        .top-hero h1 { font-family: 'Space Grotesk', sans-serif; font-size: 24px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .avatar { width: 74px; height: 74px; border-radius: 12px; border: 3px solid #fff; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #fff; background: var(--olive); }
        .body-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 24px 30px; flex: 1; }
        .sec-title { font-family: 'Space Grotesk', sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--olive-dark); border-bottom: 2px solid var(--olive); padding-bottom: 3px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px dashed #e7e5e4; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #1c1917; }
        .exp-co { font-size: 9.5px; color: var(--olive); font-weight: 600; }
        .exp-desc { font-size: 9px; color: #57534e; line-height: 1.5; margin-top: 2px; }
        .exp-desc ul { padding-left: 14px; }
        .tag { display: inline-block; font-size: 8.5px; padding: 2px 7px; background: #fefce8; border: 1px solid #fef08a; border-radius: 4px; color: var(--olive-dark); font-weight: 600; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="top-hero">
        <div>
            <h1>{{ $candidate['full_name'] ?? 'NOAH CHANG' }}</h1>
            <div style="font-size:10.5px;opacity:0.9;text-transform:uppercase;letter-spacing:1px;">{{ $candidate['title'] ?? '' }}</div>
            <div style="font-size:9px;margin-top:6px;opacity:0.85;">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span> &nbsp; @endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span> &nbsp; @endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
            </div>
        </div>
        <div class="avatar">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="">
            @else
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'N', 0, 1)) }}</div>
            @endif
        </div>
    </div>
    <div class="body-grid">
        <div>
            @if(!empty($summary))
            <div><div class="sec-title">PROFILE</div><p style="font-size:9.5px;line-height:1.6;color:#44403c;">{{ $summary }}</p></div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">EXPERIENCE</div>
                @foreach($experience as $e)
                <div class="exp-item">
                    <div class="exp-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:#78716c">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
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
        </div>

        <div>
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-title">EDUCATION</div>
                @foreach($education as $ed)
                <div class="exp-item">
                    <div class="exp-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:#78716c">{{ $ed['start_date'] ?? '' }}</div></div>
                    <div class="exp-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">KEY SKILLS</div>
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
</div>
</body>
</html>