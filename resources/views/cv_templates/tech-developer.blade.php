<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Tech Dev</title>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #0d1117; --card: #161b22; --border: #30363d; --green: #3fb950; --cyan: #58a6ff; --purple: #bc8cff; --text: #c9d1d9; --muted: #8b949e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Fira Code', monospace; background: #010409; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--bg); display: flex;     align-items: stretch;
        }
        @media print { body { background: var(--bg); } .cv-page { width: 100%; min-height: auto; } }
        .side { width: 35%; background: var(--card); border-right: 1px solid var(--border); padding: 24px 18px; display: flex; flex-direction: column; gap: 16px; font-size: 9.5px; }
        .main { flex: 1; padding: 24px 22px; display: flex; flex-direction: column; gap: 16px; font-size: 9.5px; }
        .avatar { width: 72px; height: 72px; border-radius: 8px; border: 2px solid var(--green); margin: 0 auto 10px; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: var(--green); }
        .sec-title { color: var(--green); font-size: 10.5px; font-weight: 700; border-bottom: 1px solid var(--border); padding-bottom: 4px; margin-bottom: 8px; }
        .sec-title::before { content: '$ '; color: var(--purple); }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; }
        .card-row { display: flex; justify-content: space-between; font-weight: 700; color: #fff; font-size: 10.5px; }
        .card-sub { color: var(--cyan); margin: 2px 0 4px; }
        .badge { font-size: 8.5px; padding: 1px 6px; border-radius: 4px; background: rgba(56,139,253,0.15); color: var(--cyan); border: 1px solid rgba(56,139,253,0.4); }
        .skill-badge { display: inline-block; font-size: 8.5px; padding: 2px 6px; background: #21262d; border: 1px solid var(--border); border-radius: 4px; color: var(--green); margin: 0 3px 4px 0; }
    
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
                    <div class="avatar-init">&lt;/&gt;</div>
                @endif
            </div>
            <div style="font-size:15px;font-weight:700;color:#fff;">{{ $candidate['full_name'] ?? 'dev_name' }}</div>
            <div style="color:var(--purple);font-size:9px;margin-top:2px;">// {{ $candidate['title'] ?? '' }}</div>
        </div>

        <div>
            <div class="sec-title">contact_info</div>
            @if(!empty($candidate['email']))<div style="margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['phone']))<div style="margin-bottom:4px;">📱 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
            @if(!empty($candidate['website']))<div style="margin-bottom:4px;">🌐 <a href="{{ $candidate['website'] }}" style="color:var(--cyan);">{{ $candidate['website'] }}</a></div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="sec-title">tech_stack</div>
            <div>
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                @if($sn)<span class="skill-badge">{{ $sn }}</span>@endif
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
        <div>
            <div class="sec-title">languages</div>
            @foreach($languages as $l)
            @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;"><span style="color:#fff;">{{ $ln }}</span><span style="color:var(--green)">{{ $lp }}</span></div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="main">
        @if(!empty($summary))
        <div>
            <div class="sec-title">about_me</div>
            <div class="card"><p style="line-height:1.6;color:var(--text)">{{ $summary }}</p></div>
        </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">git_log_experience</div>
            @foreach($experience as $e)
            <div class="card">
                <div class="card-row"><div style="color:var(--cyan)">const {{ $e['position'] ?? $e['title'] ?? 'Role' }}</div><div class="badge">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' -> ' . $e['end_date'] : ' -> HEAD' }}</div></div>
                <div class="card-sub">@ {{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
                @if(!empty($e['description']))
                <div style="line-height:1.5;color:var(--muted)">
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
            <div class="sec-title">education</div>
            @foreach($education as $ed)
            <div class="card">
                <div class="card-row"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div class="badge">{{ $ed['start_date'] ?? '' }}</div></div>
                <div style="color:var(--purple);margin-top:2px;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>