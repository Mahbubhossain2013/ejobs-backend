<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Sales Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --orange: #ea580c; --orange-bg: #fff7ed; --mint: #0d9488; --mint-bg: #f0fdfa; --text: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fff7ed; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .side { width: 34%; background: var(--mint-bg); padding: 24px 18px; display: flex; flex-direction: column; gap: 16px; border-right: 1px solid #ccfbf1; }
        .main { flex: 1; padding: 24px 26px; display: flex; flex-direction: column; gap: 16px; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--orange); overflow: hidden; margin: 0 auto 8px; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fff; background: var(--orange); }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--orange); border-bottom: 2px solid #fed7aa; padding-bottom: 3px; margin-bottom: 8px; }
        .card { background: #fff; border-radius: 8px; padding: 8px 12px; margin-bottom: 8px; border: 1px solid #ffedd5; box-shadow: 0 2px 4px rgba(234,88,12,0.04); }
        .pill { display: inline-block; font-size: 8.5px; padding: 2px 8px; background: var(--orange-bg); border: 1px solid #fed7aa; color: var(--orange); font-weight: 700; border-radius: 12px; margin: 0 3px 4px 0; }
    
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
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'O', 0, 1)) }}</div>
                @endif
            </div>
            <div style="font-size:11px;font-weight:700;color:var(--mint);">Sales & Strategy</div>
        </div>

        <div>
            <div style="font-size:10px;font-weight:800;color:var(--mint);text-transform:uppercase;margin-bottom:6px;">Contact</div>
            @if(!empty($candidate['phone']))<div style="font-size:9px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div style="font-size:9px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="font-size:9px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div style="font-size:10px;font-weight:800;color:var(--mint);text-transform:uppercase;margin-bottom:6px;">Key Skills</div>
            <div>
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                @if($sn)<span class="pill" style="background:#ccfbf1;border-color:#99f6e4;color:#0f766e;">{{ $sn }}</span>@endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="main">
        <div>
            <h1 style="font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:#0f172a;">{{ $candidate['full_name'] ?? 'Olivia Smith' }}</h1>
            <div style="font-size:10.5px;color:var(--orange);font-weight:700;text-transform:uppercase;">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($summary))
        <div><div class="sec-title">Professional Summary</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">Work Experience</div>
            @foreach($experience as $e)
            <div class="card">
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:#0f172a;"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:#78716c;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                <div style="font-size:9.5px;color:var(--orange);font-weight:600;margin:1px 0 2px;">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
                @if(!empty($e['description']))
                <div style="font-size:9px;color:#475569;line-height:1.5;">
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
                <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:#78716c;">{{ $ed['start_date'] ?? '' }}</div></div>
                <div style="font-size:9.5px;color:var(--mint);font-weight:600;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>