<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - UI UX Developer</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --dark: #0f172a; --slate: #475569; --purple: #8b5cf6; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .hero { background: linear-gradient(135deg, #334155, #475569); color: #fff; padding: 22px 30px; text-align: center; }
        .hero h1 { font-family: 'Cinzel', serif; font-size: 22px; font-weight: 700; letter-spacing: 3px; }
        .avatar-circle { width: 72px; height: 72px; border-radius: 50%; border: 3px solid #fff; margin: 10px auto 0; overflow: hidden; }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #fff; background: #64748b; }
        .body-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; padding: 24px 30px; flex: 1; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 8px; }
        .card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #0f172a; }
        .exp-co { font-size: 9.5px; color: #64748b; font-weight: 600; margin-bottom: 2px; }
        .exp-desc { font-size: 9px; color: #475569; line-height: 1.5; }
        .exp-desc ul { padding-left: 14px; }
        .tag { display: inline-block; font-size: 8.5px; padding: 2px 7px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; border-radius: 4px; font-weight: 600; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="hero">
        <h1>{{ $candidate['full_name'] ?? 'JAIRO & MARCI' }}</h1>
        <div style="font-size:10px;opacity:0.9;letter-spacing:2px;text-transform:uppercase;">{{ $candidate['title'] ?? '' }}</div>
        <div class="avatar-circle">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="">
            @else
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
            @endif
        </div>
    </div>

    <div class="body-grid">
        <div>
            @if(!empty($summary))
            <div><div class="sec-title">PROFILE</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">WORK HISTORY</div>
                @foreach($experience as $e)
                <div class="card">
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
        </div>

        <div>
            <div>
                <div class="sec-title">CONTACT</div>
                @if(!empty($candidate['phone']))<div style="font-size:9px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
                @if(!empty($candidate['email']))<div style="font-size:9px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
                @if(!empty($candidate['location']))<div style="font-size:9px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
            </div>

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">SKILLS</div>
                <div>
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span class="tag">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($education) && count($education) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">EDUCATION</div>
                @foreach($education as $ed)
                <div style="margin-bottom:6px;">
                    <div style="font-size:10px;font-weight:700;">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                    <div style="font-size:9px;color:var(--muted)">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>