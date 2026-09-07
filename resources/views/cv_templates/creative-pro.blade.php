<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Creative Professional</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fraunces:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --cv-primary: var(--primary-color, #7c3aed); --cv-accent: #a78bfa; --cv-text: var(--text-color, #1e293b); --cv-bg: var(--bg-color, #ffffff); --cv-muted: #64748b; --cv-border: #e2e8f0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--cv-text); background: #f1f5f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--cv-bg); box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        @media print { body { background: white; } .cv-page { box-shadow: none; margin: 0; width: 100%; } }
        .hero { background: linear-gradient(135deg, var(--cv-primary), #6d28d9, #4f46e5); color: white; padding: 32px 36px; display: flex; align-items: center; gap: 24px; }
        .hero-photo { width: 90px; height: 90px; border-radius: 50%; overflow: hidden; border: 3px solid rgba(255,255,255,0.3); flex-shrink: 0; }
        .hero-photo img { width: 100%; height: 100%; object-fit: cover; }
        .hero-fallback { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.15); font-size: 32px; font-weight: 700; }
        .hero-info h1 { font-family: 'Fraunces', serif; font-size: 26px; font-weight: 700; }
        .hero-info .title { font-size: 12px; opacity: 0.85; margin-top: 2px; }
        .hero-contact { margin-top: 8px; display: flex; gap: 16px; font-size: 10px; opacity: 0.8; flex-wrap: wrap; }
        .content { padding: 28px 36px; display: flex; flex-direction: column; gap: 18px; }
        .section h2 { font-family: 'Fraunces', serif; font-size: 14px; font-weight: 700; color: var(--cv-primary); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .section h2::after { content: ''; flex: 1; height: 1px; background: var(--cv-border); }
        .summary { font-size: 11px; line-height: 1.7; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .exp-item { margin-bottom: 12px; display: flex; gap: 12px; }
        .exp-line { width: 2px; background: linear-gradient(to bottom, var(--cv-primary), var(--cv-accent)); flex-shrink: 0; border-radius: 1px; }
        .exp-content { flex: 1; }
        .exp-row { display: flex; justify-content: space-between; align-items: flex-start; }
        .exp-title { font-size: 12px; font-weight: 700; }
        .exp-date { font-size: 9.5px; color: var(--cv-muted); }
        .exp-company { font-size: 11px; color: var(--cv-primary); font-weight: 500; margin-bottom: 2px; }
        .exp-desc { font-size: 10.5px; line-height: 1.5; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .exp-desc ul { padding-left: 14px; } .exp-desc li { margin-bottom: 1px; word-wrap: break-word; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .edu-item { margin-bottom: 8px; }
        .edu-degree { font-size: 12px; font-weight: 600; }
        .edu-school { font-size: 10.5px; color: var(--cv-primary); }
        .edu-detail { font-size: 10px; color: var(--cv-muted); }
        .skill-grid { display: flex; flex-wrap: wrap; gap: 6px; }
        .skill-chip { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: var(--cv-primary); font-size: 10px; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
        .cert-item { font-size: 10.5px; margin-bottom: 3px; }
        .lang-item { display: flex; justify-content: space-between; font-size: 10.5px; margin-bottom: 3px; }
        .project-item { margin-bottom: 6px; }
        .project-name { font-size: 11px; font-weight: 600; }
        .project-desc { font-size: 10px; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .social-item { font-size: 10px; margin-bottom: 3px; }
        .social-item a { color: var(--cv-primary); text-decoration: none; }
        .award-item { font-size: 10.5px; margin-bottom: 3px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="hero">
        <div class="hero-photo">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
            @else
                <div class="hero-fallback">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
            @endif
        </div>
        <div class="hero-info">
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
            <div class="hero-contact">
                @if(!empty($candidate['email']))<span>{{ $candidate['email'] }}</span>@endif
                @if(!empty($candidate['phone']))<span>{{ $candidate['phone'] }}</span>@endif
                @if(!empty($candidate['location']))<span>{{ $candidate['location'] }}</span>@endif
                @if(!empty($candidate['website']))<span>{{ $candidate['website'] }}</span>@endif
                @if(!empty($candidate['date_of_birth']))<span>{{ $candidate['date_of_birth'] }}</span>@endif
                @if(!empty($candidate['gender']))<span>{{ $candidate['gender'] }}</span>@endif
                @if(!empty($candidate['nationality']))<span>{{ $candidate['nationality'] }}</span>@endif
                @if(!empty($candidate['father_name']))<span>F: {{ $candidate['father_name'] }}</span>@endif
                @if(!empty($candidate['mother_name']))<span>M: {{ $candidate['mother_name'] }}</span>@endif
                @if(!empty($candidate['religion']))<span>{{ $candidate['religion'] }}</span>@endif
                @if(!empty($candidate['blood_group']))<span>Blood: {{ $candidate['blood_group'] }}</span>@endif
                @if(!empty($candidate['marital_status']))<span>{{ $candidate['marital_status'] }}</span>@endif
            </div>
        </div>
    </div>
    <div class="content">
        @if(!empty($summary))
            <div class="section"><h2>About Me</h2><div class="summary">{{ $summary }}</div></div>
        @endif
        @if(!empty($experience) && count($experience) > 0)
            <div class="section">
                <h2>Work Experience</h2>
                @foreach($experience as $exp)
                    <div class="exp-item">
                        <div class="exp-line"></div>
                        <div class="exp-content">
                            <div class="exp-row"><div class="exp-title">{{ $exp['position'] ?? $exp['title'] ?? '' }}</div><div class="exp-date">{{ $exp['start_date'] ?? '' }}{{ !empty($exp['end_date']) ? ' – ' . $exp['end_date'] : ' – Present' }}</div></div>
                            <div class="exp-company">{{ $exp['company'] ?? $exp['company_name'] ?? '' }}@if(!empty($exp['location'])) · {{ $exp['location'] }}@endif</div>
                            @if(!empty($exp['description']))<div class="exp-desc">@if(preg_match('/<[^>]+>/', $exp['description'])){!! $exp['description']!!}@else@php $lines = preg_split('/\r\n|\r|\n/', $exp['description']); $lines = array_filter(array_map('trim', $lines)); if (count($lines) <= 1 && str_contains($exp['description'], ',')) { $lines = array_filter(array_map('trim', explode(',', $exp['description']))); } if (count($lines) <= 1 && str_contains($exp['description'], ' • ')) { $lines = array_filter(array_map('trim', explode(' • ', $exp['description']))); } @endphp@if(count($lines) > 1)<ul>@foreach($lines as $line)<li>{{ $line }}</li>@endforeach</ul>@else<p>{{ $exp['description'] }}</p>@endif@endif</div>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="two-col">
            @if(!empty($education) && count($education) > 0)
                <div class="section">
                    <h2>Education</h2>
                    @foreach($education as $edu)<div class="edu-item"><div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div><div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div><div class="edu-detail">{{ $edu['start_date'] ?? '' }}@if(!empty($edu['end_date'])) – {{ $edu['end_date'] }}@endif@if(!empty($edu['grade'])) · {{ $edu['grade'] }}@elseif(!empty($edu['description'])) · {{ $edu['description'] }}@endif</div></div>@endforeach
                </div>
            @endif
            @if(!empty($skills) && count($skills) > 0)
                <div class="section">
                    <h2>Skills</h2>
                    <div class="skill-grid">@foreach($skills as $skill)<span class="skill-chip">{{ is_array($skill) ? ($skill['name'] ?? '') : $skill }}</span>@endforeach</div>
                </div>
            @endif
        </div>
        @if(!empty($projects) && count($projects) > 0)
            <div class="section">
                <h2>Projects</h2>
                @foreach($projects as $p)<div class="project-item"><div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;"><div class="project-name">{{ $p['name'] ?? $p['title'] ?? '' }}</div>@if(!empty($p['url']))<a href="{{ $p['url'] }}" style="font-size:10px;color:var(--cv-primary);white-space:nowrap;text-decoration:none;">{{ $p['url'] }}</a>@endif</div>@if(!empty($p['technologies']))<div style="font-size:10px;color:var(--cv-primary);margin-bottom:3px;">{{ is_array($p['technologies']) ? implode(', ', $p['technologies']) : $p['technologies'] }}</div>@endif@if(!empty($p['description']))<div class="project-desc">{{ $p['description'] }}</div>@endif</div>@endforeach
            </div>
        @endif
        @if(!empty($languages) && count($languages) > 0)
            <div class="section">
                <h2>Languages</h2>
                @foreach($languages as $lang)<div class="lang-item"><span>{{ is_array($lang) ? ($lang['name'] ?? '') : $lang }}</span><span>{{ is_array($lang) ? ($lang['proficiency'] ?? 'Intermediate') : 'Intermediate' }}</span></div>@endforeach
            </div>
        @endif
        @if(!empty($certifications) && count($certifications) > 0)
            <div class="section">
                <h2>Certifications</h2>
                @foreach($certifications as $cert)@php $cn=is_array($cert)?($cert['name']??$cert['title']??''):$cert;$ci=is_array($cert)?($cert['issuer']??''):'';$certDate=is_array($cert)?($cert['date']??''):'';if($certDate){try{$certDate=\Carbon\Carbon::parse($certDate)->format('M Y');}catch(\Exception$e){$certDate=$certDate;}}@endphp<div class="cert-item">{{ $cn }}@if($ci) — {{ $ci }}@endif@if($certDate) <span style="font-size:9px;color:var(--cv-muted);">({{ $certDate }})</span>@endif</div>@endforeach
            </div>
        @endif
        @if(!empty($awards) && count($awards) > 0)
            <div class="section">
                <h2>Awards</h2>
                @foreach($awards as $award)<div class="award-item">{{ is_array($award) ? ($award['name'] ?? $award['title'] ?? '') : $award }}</div>@endforeach
            </div>
        @endif
        @if(!empty($social_links) && count($social_links) > 0)
            <div class="section">
                <h2>Profiles</h2>
                @foreach($social_links as $link)@php $lu=is_array($link)?($link['url']??$link['link']??''):$link;$ll2=is_array($link)?($link['label']??$link['platform']??''):'';@endphp @if($lu)<div class="social-item">@if($ll2)<strong>{{ $ll2 }}:</strong> @endif<a href="{{ $lu }}">{{ $lu }}</a></div>@endif @endforeach
            </div>
        @endif
        @if(!empty($training) && count($training) > 0)
            <div class="section">
                <h2>Training</h2>
                @foreach($training as $t)<div class="cert-item"><span style="font-weight:600;">{{ $t['title'] ?? '' }}</span>@if(!empty($t['institute'])) — {{ $t['institute'] }}@endif@if(!empty($t['duration'])) <span style="font-size:9px;color:var(--cv-muted);">({{ $t['duration'] }})</span>@endif</div>@endforeach
            </div>
        @endif
        @if(!empty($references) && count($references) > 0)
            <div class="section">
                <h2>References</h2>
                @foreach($references as $ref)<div class="cert-item"><span style="font-weight:600;">{{ $ref['name'] ?? '' }}</span>@if(!empty($ref['designation'])) — {{ $ref['designation'] }}@endif@if(!empty($ref['organization']))<br/><span style="font-size:9.5px;color:var(--cv-muted);">{{ $ref['organization'] }}</span>@endif</div>@endforeach
            </div>
        @endif

        {{-- HOBBIES --}}
        @if(!empty($hobbies) && count($hobbies) > 0)
            <div class="section">
                <h2>Hobbies & Interests</h2>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($hobbies as $hobby)
                        <span style="font-size:11px;background:rgba(0,0,0,0.04);padding:3px 10px;border-radius:3px;">
                            {{ is_array($hobby) ? ($hobby['name'] ?? $hobby['title'] ?? '') : $hobby }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
</body>
</html>
