<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Dark Navy</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Cinzel:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root { --navy-bg: #090d16; --navy-card: #111827; --navy-sidebar: #0b1120; --gold: #f59e0b; --gold-light: #fde68a; --gold-gradient: linear-gradient(135deg, #d97706, #fbbf24); --text: #e2e8f0; --text-muted: #94a3b8; --border: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #050811; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--navy-bg); display: flex;     align-items: stretch;
        }
        @media print { body { background: var(--navy-bg); } .cv-page { width: 100%; min-height: auto; } }
        .sidebar { width: 35%; background: var(--navy-sidebar); border-right: 1px solid var(--border); padding: 26px 20px; display: flex; flex-direction: column; gap: 18px; }
        .main { flex: 1; padding: 28px 24px; display: flex; flex-direction: column; gap: 18px; background: var(--navy-bg); }
        .avatar-wrap { width: 84px; height: 84px; border-radius: 50%; margin: 0 auto 12px; padding: 3px; background: var(--gold-gradient); }
        .avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; background: #1e293b; }
        .avatar-init { width: 100%; height: 100%; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #0f172a; font-family: 'Cinzel', serif; font-size: 30px; font-weight: 700; color: var(--gold); }
        .name-box { text-align: center; }
        .name-box h1 { font-family: 'Cinzel', serif; font-size: 18px; font-weight: 700; color: #fff; letter-spacing: 1.5px; text-transform: uppercase; }
        .name-box .title { font-size: 10px; color: var(--gold); text-transform: uppercase; letter-spacing: 2px; margin-top: 4px; font-weight: 600; }
        .gold-divider { width: 36px; height: 2px; background: var(--gold-gradient); margin: 10px auto; border-radius: 2px; }
        .sec-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.8px; color: var(--gold); padding-bottom: 5px; border-bottom: 1px solid var(--border); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .contact-item { display: flex; align-items: flex-start; gap: 8px; font-size: 9.5px; color: var(--text-muted); margin-bottom: 6px; word-break: break-all; }
        .contact-item a { color: var(--gold-light); text-decoration: none; }
        .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--gold); margin-top: 4px; flex-shrink: 0; }
        .skill-bar-row { margin-bottom: 6px; }
        .skill-bar-header { display: flex; justify-content: space-between; font-size: 9.5px; margin-bottom: 2px; color: #f8fafc; }
        .skill-bar-bg { width: 100%; height: 4px; background: #1e293b; border-radius: 2px; overflow: hidden; }
        .skill-bar-fill { height: 100%; background: var(--gold-gradient); border-radius: 2px; }
        .card { background: var(--navy-card); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; margin-bottom: 8px; }
        .card-header { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; margin-bottom: 3px; }
        .card-title { font-size: 11.5px; font-weight: 700; color: #fff; }
        .card-badge { font-size: 9px; font-weight: 600; padding: 2px 7px; border-radius: 12px; background: rgba(245,158,11,0.15); color: var(--gold-light); border: 1px solid rgba(245,158,11,0.3); white-space: nowrap; }
        .card-sub { font-size: 10px; color: var(--gold); font-weight: 600; margin-bottom: 4px; }
        .card-desc { font-size: 9.5px; line-height: 1.6; color: var(--text-muted); }
        .card-desc ul { padding-left: 14px; }
        .pill { display: inline-block; font-size: 9px; padding: 2px 8px; border-radius: 4px; background: #1e293b; border: 1px solid var(--border); color: var(--text-muted); margin: 0 3px 4px 0; }
        .pill-gold { background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.3); color: var(--gold-light); }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="sidebar">
        <div>
            <div class="avatar-wrap">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" class="avatar-img" alt="">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
                @endif
            </div>
            <div class="name-box">
                <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
                @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
                <div class="gold-divider"></div>
            </div>
        </div>

        <div>
            <div class="sec-title">Contact</div>
            @if(!empty($candidate['phone']))<div class="contact-item"><div class="dot"></div><div>{{ $candidate['phone'] }}</div></div>@endif
            @if(!empty($candidate['email']))<div class="contact-item"><div class="dot"></div><div>{{ $candidate['email'] }}</div></div>@endif
            @if(!empty($candidate['location']))<div class="contact-item"><div class="dot"></div><div>{{ $candidate['location'] }}</div></div>@endif
            @if(!empty($candidate['website']))<div class="contact-item"><div class="dot"></div><div><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></div></div>@endif
            @if(!empty($candidate['nationality']))<div class="contact-item"><div class="dot"></div><div>{{ $candidate['nationality'] }}</div></div>@endif
            @if(!empty($candidate['marital_status']))<div class="contact-item"><div class="dot"></div><div>{{ $candidate['marital_status'] }}</div></div>@endif
            @if(!empty($candidate['dob']))<div class="contact-item"><div class="dot"></div><div>{{ $candidate['dob'] }}</div></div>@endif
        </div>

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="sec-title">Skills</div>
            @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; $lvl = is_array($s) ? ($s['level'] ?? '') : null; $pct = ['expert'=>95,'advanced'=>85,'intermediate'=>65,'beginner'=>40][strtolower($lvl ?? '')] ?? 75; @endphp
                @if($sn)
                <div class="skill-bar-row">
                    <div class="skill-bar-header"><span>{{ $sn }}</span><span style="color:var(--gold);font-size:8.5px;">{{ $lvl ?? '' }}</span></div>
                    <div class="skill-bar-bg"><div class="skill-bar-fill" style="width:{{ $pct }}%"></div></div>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
        <div>
            <div class="sec-title">Languages</div>
            @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                <div style="display:flex;justify-content:space-between;font-size:9.5px;margin-bottom:4px;"><span style="color:#fff;">{{ $ln }}</span><span style="color:var(--gold);">{{ $lp }}</span></div>
            @endforeach
        </div>
        @endif

        @if(!empty($social_links) && count($social_links) > 0)
        <div>
            <div class="sec-title">Social Links</div>
            @foreach($social_links as $sl)
                @php $lu = is_array($sl) ? ($sl['url'] ?? '') : $sl; $ll = is_array($sl) ? ($sl['label'] ?? $sl['platform'] ?? '') : ''; @endphp
                @if($lu)<div class="contact-item"><div class="dot"></div><div>@if($ll)<strong style="color:#fff;">{{ $ll }}:</strong> @endif<a href="{{ $lu }}">{{ $lu }}</a></div></div>@endif
            @endforeach
        </div>
        @endif
    </div>

    <div class="main">
        @if(!empty($summary))
        <div>
            <div class="sec-title">Profile Summary</div>
            <div class="card"><p style="font-size:10px;line-height:1.7;color:var(--text-muted);">{{ $summary }}</p></div>
        </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">Work Experience</div>
            @foreach($experience as $e)
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ $e['position'] ?? $e['title'] ?? '' }}</div>
                    <div class="card-badge">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div>
                </div>
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
                <div class="card-header">
                    <div class="card-title">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                    <div class="card-badge">{{ $ed['start_date'] ?? '' }}@if(!empty($ed['end_date'])) – {{ $ed['end_date'] }}@endif</div>
                </div>
                <div class="card-sub">{{ $ed['institution'] ?? $ed['school'] ?? '' }}@if(!empty($ed['location'])) · {{ $ed['location'] }}@endif</div>
                @if(!empty($ed['grade']))<div style="font-size:9.5px;color:var(--text-muted);">Grade/Result: {{ $ed['grade'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($projects) && count($projects) > 0)
        <div>
            <div class="sec-title">Projects</div>
            @foreach($projects as $p)
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ $p['name'] ?? $p['title'] ?? '' }}</div>
                    @if(!empty($p['url']))<a href="{{ $p['url'] }}" style="font-size:9px;color:var(--gold-light);text-decoration:none;">{{ $p['url'] }}</a>@endif
                </div>
                @if(!empty($p['description']))<div class="card-desc">{{ $p['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($certifications) && count($certifications) > 0)
        <div>
            <div class="sec-title">Certifications</div>
            @foreach($certifications as $c)
            @php $cn = is_array($c) ? ($c['name'] ?? $c['title'] ?? '') : $c; $ci = is_array($c) ? ($c['issuer'] ?? '') : ''; @endphp
            <div style="font-size:10px;margin-bottom:4px;color:#fff;"><strong style="color:var(--gold-light);">{{ $cn }}</strong>@if($ci) — <span style="color:var(--text-muted)">{{ $ci }}</span>@endif</div>
            @endforeach
        </div>
        @endif

        @if(!empty($hobbies) && count($hobbies) > 0)
        <div>
            <div class="sec-title">Hobbies & Interests</div>
            <div>@foreach($hobbies as $h)<span class="pill pill-gold">{{ is_array($h) ? ($h['name'] ?? $h['title'] ?? '') : $h }}</span>@endforeach</div>
        </div>
        @endif
    </div>
</div>
</body>
</html>