<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Fresher Resume</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --green-dark: #1a5c38; --green-mid: #2e7d52; --green-light: #4caf80; --green-accent: #3d8c5e; --green-bg: #e8f5ee; --white: #ffffff; --text-dark: #1a2e22; --text-mid: #3a5040; --text-light: #6b8575; --border: #c5dfd0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Open Sans", sans-serif; color: var(--text-dark); background: #f0f4f1; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--white); display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: white; } .cv-page { box-shadow: none; margin: 0; width: 100%; min-height: auto; } }
        .header-banner { background: var(--green-dark); padding: 10px 24px; display: flex; align-items: center; justify-content: space-between; }
        .header-banner h1 { font-family: "Poppins", sans-serif; font-size: 11px; font-weight: 700; color: var(--white); text-transform: uppercase; letter-spacing: 2px; }
        .header-banner .hline { flex: 1; height: 1px; background: rgba(255,255,255,0.25); margin: 0 16px; }
        .header-banner .hbadge { font-size: 8px; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 1px; }
        .name-section { background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-mid) 60%, var(--green-light) 100%); padding: 20px 28px 18px; display: flex; align-items: center; gap: 20px; position: relative; overflow: hidden; }
        .name-section::before { content: ""; position: absolute; top: -30px; right: -30px; width: 130px; height: 130px; border-radius: 50%; background: rgba(255,255,255,0.06); }
        .name-section::after { content: ""; position: absolute; bottom: -50px; left: 20%; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,0.04); }
        .name-photo { position: relative; z-index: 1; width: 76px; height: 76px; border-radius: 12px; overflow: hidden; border: 3px solid rgba(255,255,255,0.4); flex-shrink: 0; }
        .name-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .name-photo-fallback { width: 100%; height: 100%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-family: "Poppins", sans-serif; font-size: 28px; font-weight: 700; color: white; }
        .name-text { position: relative; z-index: 1; flex: 1; }
        .name-text h2 { font-family: "Poppins", sans-serif; font-size: 24px; font-weight: 800; color: var(--white); letter-spacing: 0.5px; text-transform: uppercase; line-height: 1.1; }
        .name-text .job-title { font-size: 10px; font-weight: 400; color: rgba(255,255,255,0.82); margin-top: 3px; letter-spacing: 0.5px; }
        .name-divider { width: 36px; height: 2px; background: rgba(255,255,255,0.5); margin: 6px 0; border-radius: 2px; }
        .body-wrap { display: flex; flex: 1; }
        .left-panel { width: 37%; background: var(--green-bg); padding: 20px 16px; display: flex; flex-direction: column; gap: 16px; }
        .right-panel { flex: 1; padding: 20px 20px; display: flex; flex-direction: column; gap: 16px; background: var(--white); }
        .section-title { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .section-title .icon-box { width: 22px; height: 22px; background: var(--green-dark); border-radius: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .section-title .icon-box svg { width: 12px; height: 12px; fill: white; }
        .section-title h3 { font-family: "Poppins", sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--green-dark); }
        .right-panel .section-title h3 { color: var(--text-dark); }
        .right-panel .section-title .icon-box { background: var(--green-accent); }
        .section-heading-line { flex: 1; height: 1.5px; background: var(--green-light); margin-left: 4px; border-radius: 2px; }
        .objective-text { font-size: 10px; line-height: 1.75; color: var(--text-mid); word-wrap: break-word; overflow-wrap: break-word; }
        .skill-dot-list { list-style: none; padding: 0; margin: 0; }
        .skill-dot-list li { font-size: 10px; color: var(--text-mid); padding: 2px 0 2px 14px; position: relative; line-height: 1.5; }
        .skill-dot-list li::before { content: ""; position: absolute; left: 0; top: 7px; width: 6px; height: 6px; border-radius: 50%; background: var(--green-dark); }
        .contact-item { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
        .contact-item .ci-icon { width: 18px; height: 18px; background: var(--green-dark); border-radius: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }
        .contact-item .ci-icon svg { width: 10px; height: 10px; fill: none; stroke: white; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .contact-item .ci-text { font-size: 10px; color: var(--text-mid); word-break: break-all; line-height: 1.4; }
        .contact-item .ci-text a { color: var(--green-dark); text-decoration: none; }
        .lang-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; }
        .lang-name { font-size: 10px; font-weight: 600; color: var(--text-dark); }
        .lang-bar-wrap { width: 70px; height: 5px; background: var(--border); border-radius: 3px; overflow: hidden; }
        .lang-bar { height: 100%; background: var(--green-dark); border-radius: 3px; }
        .edu-item { margin-bottom: 10px; padding-left: 10px; border-left: 3px solid var(--green-light); }
        .edu-degree { font-size: 11px; font-weight: 700; color: var(--text-dark); line-height: 1.3; }
        .edu-school { font-size: 10px; color: var(--green-accent); margin: 1px 0; }
        .edu-meta { font-size: 9.5px; color: var(--text-light); }
        .exp-item { margin-bottom: 10px; }
        .exp-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
        .exp-title { font-size: 11px; font-weight: 700; color: var(--text-dark); }
        .exp-date { font-size: 9px; color: var(--white); background: var(--green-accent); padding: 2px 7px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
        .exp-company { font-size: 10px; color: var(--green-accent); font-weight: 600; margin: 2px 0 4px; }
        .exp-desc { font-size: 10px; line-height: 1.65; color: var(--text-light); word-wrap: break-word; overflow-wrap: break-word; }
        .exp-desc ul { padding-left: 14px; }
        .exp-desc li { margin-bottom: 2px; }
        .skill-tags { display: flex; flex-wrap: wrap; gap: 5px; }
        .skill-tag { font-size: 9.5px; padding: 3px 10px; background: var(--green-bg); border: 1px solid var(--border); border-radius: 20px; color: var(--green-dark); font-weight: 500; }
        .hobby-list { display: flex; flex-wrap: wrap; gap: 5px; }
        .hobby-tag { font-size: 9.5px; padding: 3px 10px; background: var(--green-dark); border-radius: 20px; color: white; font-weight: 400; }
        .project-item { margin-bottom: 8px; }
        .project-name { font-size: 11px; font-weight: 700; color: var(--text-dark); }
        .project-url { font-size: 9px; color: var(--green-accent); }
        .project-desc { font-size: 10px; color: var(--text-light); margin-top: 2px; word-wrap: break-word; overflow-wrap: break-word; }
        .cert-item { font-size: 10px; margin-bottom: 5px; padding-left: 10px; border-left: 2px solid var(--green-light); }
        .cert-name { font-weight: 600; color: var(--text-dark); }
        .cert-meta { font-size: 9px; color: var(--text-light); }
        .award-item { font-size: 10px; margin-bottom: 4px; color: var(--text-mid); padding-left: 14px; position: relative; }
        .award-item::before { content: "★"; position: absolute; left: 0; color: var(--green-accent); font-size: 9px; }
        .training-item { font-size: 10px; margin-bottom: 5px; padding-left: 10px; border-left: 2px solid var(--green-light); }
        .training-title { font-weight: 600; color: var(--text-dark); }
        .training-meta { font-size: 9px; color: var(--text-light); }
        .ref-item { font-size: 10px; margin-bottom: 6px; }
        .ref-name { font-weight: 700; color: var(--text-dark); }
        .ref-detail { color: var(--text-light); font-size: 9.5px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header-banner">
        <h1>Fresher Resume</h1>
        <div class="hline"></div>
        <span class="hbadge">Curriculum Vitae</span>
    </div>
    <div class="name-section">
        <div class="name-photo">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
            @else
                <div class="name-photo-fallback">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
            @endif
        </div>
        <div class="name-text">
            <h2>{{ $candidate['full_name'] ?? 'Your Name' }}</h2>
            <div class="name-divider"></div>
            @if(!empty($candidate['title']))<div class="job-title">{{ $candidate['title'] }}</div>@endif
        </div>
    </div>
    <div class="body-wrap">
        <div class="left-panel">
            @if(!empty($summary))
            <div>
                <div class="section-title">
                    <div class="icon-box"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                    <h3>Career Objective</h3>
                </div>
                <div class="objective-text">{{ $summary }}</div>
            </div>
            @endif
            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="section-title">
                    <div class="icon-box"><svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5S14.7 2 12 2 7 4.3 7 7s2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg></div>
                    <h3>Personal Skills</h3>
                </div>
                <ul class="skill-dot-list">
                    @foreach($skills as $skill)
                        @php $sn = is_array($skill) ? ($skill['name'] ?? $skill['label'] ?? '') : $skill; @endphp
                        @if($sn)<li>{{ $sn }}</li>@endif
                    @endforeach
                </ul>
            </div>
            @endif
            <div>
                <div class="section-title">
                    <div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg></div>
                    <h3>Contact</h3>
                </div>
                @if(!empty($candidate['phone']))<div class="contact-item"><div class="ci-icon"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg></div><div class="ci-text">{{ $candidate['phone'] }}</div></div>@endif
                @if(!empty($candidate['email']))<div class="contact-item"><div class="ci-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div><div class="ci-text">{{ $candidate['email'] }}</div></div>@endif
                @if(!empty($candidate['location']))<div class="contact-item"><div class="ci-icon"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></div><div class="ci-text">{{ $candidate['location'] }}</div></div>@endif
                @if(!empty($candidate['nationality']))<div class="contact-item"><div class="ci-icon"><svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></div><div class="ci-text">{{ $candidate['nationality'] }}</div></div>@endif
                @if(!empty($candidate['marital_status']))<div class="contact-item"><div class="ci-icon"><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div><div class="ci-text">{{ $candidate['marital_status'] }}</div></div>@endif
                @if(!empty($candidate['dob']))<div class="contact-item"><div class="ci-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="ci-text">{{ $candidate['dob'] }}</div></div>@endif
            </div>
            @if(!empty($languages) && count($languages) > 0)
            <div>
                <div class="section-title">
                    <div class="icon-box"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div>
                    <h3>Languages</h3>
                </div>
                @foreach($languages as $lang)
                    @php $ln=is_array($lang)?($lang['name']??$lang['language']??''):$lang; $lp=is_array($lang)?($lang['proficiency']??$lang['level']??''):''; $bm=['native'=>100,'fluent'=>90,'advanced'=>75,'upper intermediate'=>65,'intermediate'=>55,'elementary'=>35,'beginner'=>20]; $bw=$bm[strtolower($lp)]??60; @endphp
                    <div class="lang-row"><span class="lang-name">{{ $ln }}</span><div class="lang-bar-wrap"><div class="lang-bar" style="width:{{ $bw }}%"></div></div></div>
                @endforeach
            </div>
            @endif
            @if(!empty($social_links) && count($social_links) > 0)
            <div>
                <div class="section-title">
                    <div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></div>
                    <h3>Social Links</h3>
                </div>
                @foreach($social_links as $link)
                    @php $lu=is_array($link)?($link['url']??''):$link; $ll=is_array($link)?($link['label']??$link['platform']??''):''; @endphp
                    @if($lu)<div class="contact-item"><div class="ci-icon"><svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg></div><div class="ci-text">@if($ll)<strong>{{ $ll }}:</strong> @endif<a href="{{ $lu }}">{{ $lu }}</a></div></div>@endif
                @endforeach
            </div>
            @endif
        </div>
        <div class="right-panel">
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div><h3>Education</h3><div class="section-heading-line"></div></div>
                @foreach($education as $edu)
                <div class="edu-item">
                    <div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div>
                    <div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div>
                    <div class="edu-meta">{{ $edu['start_date'] ?? '' }}@if(!empty($edu['end_date'])) - {{ $edu['end_date'] }}@endif@if(!empty($edu['location'])) &middot; {{ $edu['location'] }}@endif@if(!empty($edu['grade'])) &middot; {{ $edu['grade'] }}@elseif(!empty($edu['description'])) &middot; {{ Str::limit($edu['description'], 80) }}@endif</div>
                </div>
                @endforeach
            </div>
            @endif
            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg></div><h3>Work Experience</h3><div class="section-heading-line"></div></div>
                @foreach($experience as $exp)
                <div class="exp-item">
                    <div class="exp-header"><div class="exp-title">{{ $exp['position'] ?? $exp['title'] ?? '' }}</div><div class="exp-date">{{ $exp['start_date'] ?? '' }}{{ !empty($exp['end_date']) ? ' - '.$exp['end_date'] : ' - Present' }}</div></div>
                    <div class="exp-company">{{ $exp['company'] ?? $exp['company_name'] ?? '' }}@if(!empty($exp['location'])) &middot; {{ $exp['location'] }}@endif</div>
                    @if(!empty($exp['description']))<div class="exp-desc">@if(preg_match('/<[^>]+>/', $exp['description'])){!! $exp['description'] !!}@else@php $lines=preg_split('/\r\n|\r|\n/',$exp['description']); $lines=array_filter(array_map('trim',$lines)); if(count($lines)<=1 && str_contains($exp['description'],',')) { $lines=array_filter(array_map('trim',explode(',',  $exp['description']))); } @endphp@if(count($lines)>1)<ul>@foreach($lines as $line)<li>{{ $line }}</li>@endforeach</ul>@else<p>{{ $exp['description'] }}</p>@endif@endif</div>@endif
                </div>
                @endforeach
            </div>
            @endif
            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div><h3>Technical Skills</h3><div class="section-heading-line"></div></div>
                <div class="skill-tags">@foreach($skills as $skill)@php $sn=is_array($skill)?($skill['name']??$skill['label']??''):$skill; @endphp@if($sn)<span class="skill-tag">{{ $sn }}</span>@endif@endforeach</div>
            </div>
            @endif
            @if(!empty($projects) && count($projects) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg></div><h3>Projects</h3><div class="section-heading-line"></div></div>
                @foreach($projects as $p)<div class="project-item"><div class="project-name">{{ $p['name'] ?? $p['title'] ?? '' }}</div>@if(!empty($p['url']))<div class="project-url">{{ $p['url'] }}</div>@endif@if(!empty($p['description']))<div class="project-desc">{{ $p['description'] }}</div>@endif</div>@endforeach
            </div>
            @endif
            @if(!empty($certifications) && count($certifications) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg></div><h3>Certifications</h3><div class="section-heading-line"></div></div>
                @foreach($certifications as $cert)@php $cn=is_array($cert)?($cert['name']??$cert['title']??''):$cert; $ci=is_array($cert)?($cert['issuer']??''):''; $cd=is_array($cert)?($cert['date']??''):''; if($cd){try{$cd=\Carbon\Carbon::parse($cd)->format('M Y');}catch(\Exception $e){}} @endphp<div class="cert-item"><div class="cert-name">{{ $cn }}</div><div class="cert-meta">@if($ci){{ $ci }}@endif@if($cd&&$ci) &middot; @endif@if($cd){{ $cd }}@endif</div></div>@endforeach
            </div>
            @endif
            @if(!empty($training) && count($training) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg></div><h3>Training</h3><div class="section-heading-line"></div></div>
                @foreach($training as $t)<div class="training-item"><div class="training-title">{{ $t['title'] ?? '' }}</div><div class="training-meta">@if(!empty($t['institute'])){{ $t['institute'] }}@endif@if(!empty($t['duration'])) &middot; {{ $t['duration'] }}@endif</div></div>@endforeach
            </div>
            @endif
            @if(!empty($hobbies) && count($hobbies) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div><h3>Hobbies</h3><div class="section-heading-line"></div></div>
                <div class="hobby-list">@foreach($hobbies as $hobby)<span class="hobby-tag">{{ is_array($hobby)?($hobby['name']??$hobby['title']??''):$hobby }}</span>@endforeach</div>
            </div>
            @endif
            @if(!empty($awards) && count($awards) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/><circle cx="12" cy="8" r="6"/></svg></div><h3>Awards &amp; Honors</h3><div class="section-heading-line"></div></div>
                @foreach($awards as $award)<div class="award-item">{{ is_array($award)?($award['name']??$award['title']??''):$award }}</div>@endforeach
            </div>
            @endif
            @if(!empty($references) && count($references) > 0)
            <div>
                <div class="section-title"><div class="icon-box"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div><h3>References</h3><div class="section-heading-line"></div></div>
                @foreach($references as $ref)<div class="ref-item"><div class="ref-name">{{ $ref['name'] ?? '' }}</div><div class="ref-detail">@if(!empty($ref['designation'])){{ $ref['designation'] }}@endif@if(!empty($ref['organization'])) &middot; {{ $ref['organization'] }}@endif</div>@if(!empty($ref['phone']))<div class="ref-detail">{{ $ref['phone'] }}</div>@endif@if(!empty($ref['email']))<div class="ref-detail">{{ $ref['email'] }}</div>@endif</div>@endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
