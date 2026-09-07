<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Manager Resume</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --purple: #9333ea; --purple-bg: #faf5ff; --yellow-bg: #fefce8; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #faf5ff; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; padding: 30px 34px; display: flex; flex-direction: column; gap: 16px;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { padding: 20mm; width: 100%; min-height: auto; } }
        .header { display: flex; align-items: center; gap: 20px; border-bottom: 2px solid #f3e8ff; padding-bottom: 16px; }
        .avatar { width: 78px; height: 78px; border-radius: 16px; border: 3px solid #d8b4fe; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: #fff; background: var(--purple); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--purple); border-bottom: 2px solid #e9d5ff; padding-bottom: 3px; margin-bottom: 8px; }
        .card-p { background: var(--purple-bg); border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; }
        .card-y { background: var(--yellow-bg); border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; }
        .tag { display: inline-block; font-size: 8.5px; padding: 2px 8px; background: #f3e8ff; color: #7e22ce; font-weight: 600; border-radius: 12px; margin: 0 3px 4px 0; }
    
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
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'J', 0, 1)) }}</div>
            @endif
        </div>
        <div>
            <h1 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:#581c87;">{{ $candidate['full_name'] ?? 'JENNIFER FRANKS' }}</h1>
            <div style="font-size:10.5px;color:var(--purple);font-weight:700;text-transform:uppercase;letter-spacing:1px;">{{ $candidate['title'] ?? '' }}</div>
            <div style="font-size:9px;color:var(--muted);margin-top:4px;">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span> · @endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span> · @endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
            </div>
        </div>
    </div>

    @if(!empty($summary))
    <div class="card-p"><p style="font-size:9.5px;line-height:1.6;color:#581c87;">{{ $summary }}</p></div>
    @endif

    <div class="grid-2">
        <div>
            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-title">Work Experience</div>
                @foreach($experience as $e)
                <div class="card-p">
                    <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:#581c87;"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                    <div style="font-size:9.5px;color:#7e22ce;font-weight:600;margin:1px 0 2px;">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
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
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-title">Education</div>
                @foreach($education as $ed)
                <div class="card-y">
                    <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:#854d0e;"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:#a16207;">{{ $ed['start_date'] ?? '' }}</div></div>
                    <div style="font-size:9.5px;color:#ca8a04;font-weight:600;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Skills</div>
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