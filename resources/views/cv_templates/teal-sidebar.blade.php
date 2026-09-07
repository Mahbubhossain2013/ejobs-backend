<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Teal Modern</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --teal: #0d9488; --teal-dark: #0f766e; --teal-light: #ccfbf1; --teal-bg: #f0fdfa; --text: #134e4a; --text-dark: #0f172a; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Open Sans', sans-serif; background: #e6f4f1; color: var(--text-dark); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        @media print {
            html, body {
                background: linear-gradient(to right, var(--teal) 34%, #ffffff 34%) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; align-items: stretch; }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .sidebar { width: 34%; background: var(--teal); color: #fff; padding: 28px 20px; display: flex; flex-direction: column; gap: 18px; align-self: stretch; min-height: 100%; }
        .main { flex: 1; padding: 28px 26px; display: flex; flex-direction: column; gap: 18px; background: #fff; }
        .avatar { width: 88px; height: 88px; border-radius: 50%; border: 3px solid #fff; margin: 0 auto 12px; overflow: hidden; background: rgba(255,255,255,0.2); }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 700; color: #fff; }
        .name-box { text-align: center; }
        .name-box h1 { font-family: 'Montserrat', sans-serif; font-size: 19px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #fff; }
        .name-box .title { font-size: 10px; color: var(--teal-light); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 3px; font-weight: 500; }
        .side-title { font-family: 'Montserrat', sans-serif; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--teal-light); border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 4px; margin-bottom: 8px; }
        .main-title { font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--teal-dark); border-bottom: 2px solid var(--teal); padding-bottom: 4px; margin-bottom: 10px; }
        .side-item { font-size: 9.5px; color: rgba(255,255,255,0.9); margin-bottom: 5px; word-break: break-all; }
        .side-item a { color: #fff; }
        .exp-item { margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
        .exp-row { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; color: var(--text-dark); }
        .exp-company { font-size: 10px; color: var(--teal); font-weight: 600; margin: 2px 0 3px; }
        .exp-badge { font-size: 9px; padding: 2px 8px; border-radius: 10px; background: var(--teal-bg); color: var(--teal-dark); font-weight: 600; }
        .exp-desc { font-size: 9.5px; color: #475569; line-height: 1.55; }
        .exp-desc ul { padding-left: 14px; }
        .skill-tag { display: inline-block; font-size: 9px; padding: 3px 8px; border-radius: 4px; background: rgba(255,255,255,0.15); color: #fff; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="sidebar">
        <div>
            <div class="avatar">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
                @endif
            </div>
            <div class="name-box">
                <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
                @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
            </div>
        </div>

        <div>
            <div class="side-title">Contact</div>
            @if(!empty($candidate['phone']))<div class="side-item">📱 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div class="side-item">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div class="side-item">📍 {{ $candidate['location'] }}</div>@endif
            @if(!empty($candidate['website']))<div class="side-item">🌐 <a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></div>@endif
            @if(!empty($candidate['nationality']))<div class="side-item">🌍 {{ $candidate['nationality'] }}</div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="side-title">Key Skills</div>
            <div>
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                @if($sn)<span class="skill-tag">{{ $sn }}</span>@endif
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
        <div>
            <div class="side-title">Languages</div>
            @foreach($languages as $l)
            @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
            <div style="display:flex;justify-content:space-between;font-size:9.5px;margin-bottom:4px;"><span>{{ $ln }}</span><span style="color:var(--teal-light)">{{ $lp }}</span></div>
            @endforeach
        </div>
        @endif

        @if(!empty($hobbies) && count($hobbies) > 0)
        <div>
            <div class="side-title">Hobbies</div>
            <div>
                @foreach($hobbies as $h)<span class="skill-tag">{{ is_array($h) ? ($h['name'] ?? $h['title'] ?? '') : $h }}</span>@endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="main">
        @if(!empty($summary))
        <div>
            <div class="main-title">Professional Summary</div>
            <p style="font-size:10px;line-height:1.65;color:#334155;">{{ $summary }}</p>
        </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="main-title">Work Experience</div>
            @foreach($experience as $e)
            <div class="exp-item">
                <div class="exp-row"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div class="exp-badge">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                <div class="exp-company">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>
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
            <div class="main-title">Education</div>
            @foreach($education as $ed)
            <div style="margin-bottom:8px;">
                <div style="font-size:11px;font-weight:700;color:var(--text-dark);">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                <div style="font-size:10px;color:var(--teal);font-weight:600;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                <div style="font-size:9px;color:var(--muted);">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($projects) && count($projects) > 0)
        <div>
            <div class="main-title">Key Projects</div>
            @foreach($projects as $p)
            <div style="margin-bottom:8px;">
                <div style="font-size:11px;font-weight:700;">{{ $p['name'] ?? $p['title'] ?? '' }}</div>
                @if(!empty($p['description']))<div style="font-size:9.5px;color:#475569;margin-top:2px;">{{ $p['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>