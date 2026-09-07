<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Blue Corporate</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --blue: #1e40af; --blue-dark: #1e3a8a; --blue-light: #eff6ff; --blue-border: #bfdbfe; --text: #0f172a; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito Sans', sans-serif; background: #f1f5f9; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .hero { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; padding: 26px 30px; display: flex; justify-content: space-between; align-items: center; }
        .hero h1 { font-size: 26px; font-weight: 800; }
        .hero .title { font-size: 11px; opacity: 0.9; margin-top: 2px; }
        .body-wrap { display: flex; flex: 1; }
        .sidebar { width: 34%; background: var(--blue-light); padding: 22px 18px; display: flex; flex-direction: column; gap: 16px; border-right: 1px solid var(--blue-border); }
        .main { flex: 1; padding: 24px 26px; display: flex; flex-direction: column; gap: 16px; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--blue-dark); border-bottom: 2px solid var(--blue); padding-bottom: 3px; margin-bottom: 8px; }
        .ci { font-size: 9.5px; color: #334155; margin-bottom: 5px; word-break: break-all; }
        .ci a { color: var(--blue); }
        .skill-tag { display: inline-block; font-size: 9px; padding: 2px 7px; background: #fff; border: 1px solid var(--blue-border); color: var(--blue); border-radius: 4px; font-weight: 600; margin: 0 3px 4px 0; }
        .card { margin-bottom: 10px; }
        .card-row { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; }
        .card-sub { font-size: 10px; color: var(--blue); font-weight: 600; margin: 1px 0 3px; }
        .card-desc { font-size: 9.5px; color: #475569; line-height: 1.55; }
        .card-desc ul { padding-left: 14px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="hero">
        <div>
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        </div>
        @if(!empty($candidate['photo_url']))
        <img src="{{ $candidate['photo_url'] }}" style="width:72px;height:72px;border-radius:50%;border:2px solid #fff;object-fit:cover;" alt="">
        @endif
    </div>
    <div class="body-wrap">
        <div class="sidebar">
            <div>
                <div class="sec-title">Contact</div>
                @if(!empty($candidate['phone']))<div class="ci">📞 {{ $candidate['phone'] }}</div>@endif
                @if(!empty($candidate['email']))<div class="ci">✉️ {{ $candidate['email'] }}</div>@endif
                @if(!empty($candidate['location']))<div class="ci">📍 {{ $candidate['location'] }}</div>@endif
                @if(!empty($candidate['website']))<div class="ci">🌐 <a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></div>@endif
            </div>

            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="sec-title">Skills</div>
                <div>@foreach($skills as $s)@php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp@if($sn)<span class="skill-tag">{{ $sn }}</span>@endif@endforeach</div>
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div>
                <div class="sec-title">Languages</div>
                @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                <div style="display:flex;justify-content:space-between;font-size:9.5px;margin-bottom:4px;"><span>{{ $ln }}</span><span style="color:var(--blue);font-weight:700;">{{ $lp }}</span></div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="main">
            @if(!empty($summary))
            <div><div class="sec-title">Summary</div><p style="font-size:10px;line-height:1.65;color:#334155;">{{ $summary }}</p></div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-title">Experience</div>
                @foreach($experience as $e)
                <div class="card">
                    <div class="card-row"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:9px;color:#64748b;">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                    <div class="card-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>
                    @if(!empty($e['description']))
                    <div class="card-desc">
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
                    <div class="card-row"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:9px;color:#64748b;">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div></div>
                    <div class="card-sub">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>