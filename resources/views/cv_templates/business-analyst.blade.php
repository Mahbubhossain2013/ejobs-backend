<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Business Analyst</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Playfair+Display:ital,wght@1,600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --taupe: #78716c; --dark: #1c1917; --text: #292524; --muted: #78716c; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Montserrat', sans-serif; background: #f5f5f4; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 32px 36px; display: flex; flex-direction: column; gap: 16px; border-top: 5px solid #78716c;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #d6d3d1; padding-bottom: 16px; }
        .avatar-framed { width: 72px; height: 72px; border: 3px solid #78716c; padding: 2px; overflow: hidden; }
        .avatar-framed img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; background: #44403c; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .script-name { font-family: 'Playfair Display', serif; font-style: italic; font-size: 28px; color: var(--dark); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .sec-title { font-family: 'Cinzel', serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #44403c; border-bottom: 1px solid #a8a29e; padding-bottom: 3px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #1c1917; }
        .exp-co { font-size: 9.5px; color: #78716c; font-style: italic; margin-bottom: 2px; }
        .exp-desc { font-size: 9px; color: #57534e; line-height: 1.5; }
        .exp-desc ul { padding-left: 14px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <div style="display:flex;align-items:center;gap:16px;">
            <div class="avatar-framed">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'S', 0, 1)) }}</div>
                @endif
            </div>
            <div>
                <div class="script-name">{{ $candidate['full_name'] ?? 'Stefan Blake' }}</div>
                <div style="font-size:10px;color:#78716c;text-transform:uppercase;letter-spacing:2px;font-weight:600;">{{ $candidate['title'] ?? '' }}</div>
            </div>
        </div>
        <div style="text-align:right;font-size:9px;color:#78716c;">
            @if(!empty($candidate['phone']))<div>📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div>✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div>📍 {{ $candidate['location'] }}</div>@endif
        </div>
    </div>

    @if(!empty($summary))
    <div><div class="sec-title">Profile</div><p style="font-size:9.5px;line-height:1.6;color:#44403c;">{{ $summary }}</p></div>
    @endif

    <div class="grid-2">
        <div>
            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-title">Professional Experience</div>
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
        </div>

        <div>
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

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Key Skills</div>
                <div>
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span style="display:inline-block;font-size:8.5px;padding:2px 7px;background:#f5f5f4;border:1px solid #d6d3d1;border-radius:4px;color:#44403c;margin:0 3px 4px 0;font-weight:600;">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>