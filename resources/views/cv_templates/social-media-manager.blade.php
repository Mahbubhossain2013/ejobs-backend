<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Social Media Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --cyan: #06b6d4; --cyan-dark: #0891b2; --cyan-light: #ecfeff; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 32px 36px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid var(--cyan); padding-bottom: 16px; }
        .avatar { width: 78px; height: 78px; border-radius: 50%; border: 3px solid var(--cyan); overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #fff; background: var(--cyan); }
        .quote-box { background: var(--cyan-light); border-left: 3px solid var(--cyan); padding: 10px 14px; border-radius: 0 6px 6px 0; font-size: 9.5px; color: #0e7490; font-style: italic; line-height: 1.5; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--cyan-dark); border-bottom: 2px solid var(--cyan-light); padding-bottom: 3px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #0f172a; }
        .exp-co { font-size: 9.5px; color: var(--cyan-dark); font-weight: 600; margin-bottom: 2px; }
        .exp-desc { font-size: 9px; color: #475569; line-height: 1.5; }
        .exp-desc ul { padding-left: 14px; }
        .pill { display: inline-block; font-size: 8.5px; padding: 2px 8px; background: var(--cyan-light); color: var(--cyan-dark); font-weight: 600; border-radius: 12px; margin: 0 3px 4px 0; }
    
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
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'A', 0, 1)) }}</div>
            @endif
        </div>
        <div style="text-align:right;">
            <h1 style="font-size:24px;font-weight:800;color:#0f172a;">{{ $candidate['full_name'] ?? 'Amelie Sanders' }}</h1>
            <div style="font-size:10.5px;color:var(--cyan-dark);font-weight:700;text-transform:uppercase;">{{ $candidate['title'] ?? '' }}</div>
            <div style="font-size:9px;color:var(--muted);margin-top:4px;">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span> · @endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span> · @endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
            </div>
        </div>
    </div>

    @if(!empty($summary))
    <div class="quote-box">“ {{ $summary }} ”</div>
    @endif

    <div class="grid-2">
        <div>
            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-title">Work Experience</div>
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
                    @if($sn)<span class="pill">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>