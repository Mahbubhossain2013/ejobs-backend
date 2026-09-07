<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Graphic Designer</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Caveat:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --dark: #1e293b; --text: #0f172a; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .header { background: #0f172a; color: #fff; padding: 24px 30px; display: flex; justify-content: space-between; align-items: center; }
        .hello-text { font-family: 'Caveat', cursive; font-size: 38px; color: #38bdf8; line-height: 1; }
        .body-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; padding: 24px 30px; flex: 1; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; border-bottom: 2px solid #0f172a; padding-bottom: 3px; margin-bottom: 8px; }
        .card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; }
        .badge { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 50%; border: 2px solid #0f172a; font-size: 8.5px; font-weight: 800; margin: 0 4px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <div>
            <div class="hello-text">Hello!</div>
            <h1 style="font-size:22px;font-weight:800;">I am {{ $candidate['full_name'] ?? 'Jane Shark' }}</h1>
            <div style="font-size:10px;color:#94a3b8;letter-spacing:1px;text-transform:uppercase;">{{ $candidate['title'] ?? '' }}</div>
        </div>
        @if(!empty($candidate['photo_url']))
            <img src="{{ $candidate['photo_url'] }}" style="width:72px;height:72px;border-radius:50%;border:2px solid #38bdf8;object-fit:cover;" alt="">
        @endif
    </div>
    <div class="body-grid">
        <div>
            @if(!empty($summary))
            <div><div class="sec-title">PROFILE</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">WORK EXPERIENCE</div>
                @foreach($experience as $e)
                <div class="card">
                    <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                    <div style="font-size:9.5px;color:#0284c7;font-weight:600;margin:1px 0 2px;">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
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
        </div>

        <div>
            <div>
                <div class="sec-title">CONTACT</div>
                @if(!empty($candidate['phone']))<div style="font-size:9px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
                @if(!empty($candidate['email']))<div style="font-size:9px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
                @if(!empty($candidate['location']))<div style="font-size:9px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
            </div>

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:12px;">
                <div class="sec-title">SOFTWARE & SKILLS</div>
                <div>
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span style="display:inline-block;font-size:8.5px;padding:2px 7px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:4px;margin:0 3px 4px 0;font-weight:600;">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($education) && count($education) > 0)
            <div style="margin-top:12px;">
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