<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Minimal Elegant</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --cv-primary: var(--primary-color, #1c1917); --cv-accent: #78716c; --cv-text: var(--text-color, #1c1917); --cv-bg: var(--bg-color, #ffffff); --cv-muted: #78716c; --cv-border: #e7e5e4; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Source Sans 3', sans-serif; color: var(--cv-text); background: #f5f5f4; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--cv-bg); padding: 48px 48px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        @media print { body { background: white; } .cv-page { box-shadow: none; margin: 0; width: 100%; padding: 24px; } }
        .header { text-align: center; margin-bottom: 28px; }
        .header h1 { font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 600; letter-spacing: -0.5px; color: var(--cv-primary); }
        .header .title { font-size: 13px; color: var(--cv-muted); margin-top: 4px; font-weight: 300; letter-spacing: 2px; text-transform: uppercase; }
        .divider { width: 40px; height: 1px; background: var(--cv-primary); margin: 16px auto; }
        .contact-row { display: flex; justify-content: center; gap: 20px; font-size: 10px; color: var(--cv-muted); }
        .contact-row a { color: var(--cv-primary); text-decoration: none; }
        .content { display: flex; flex-direction: column; gap: 22px; }
        .section h2 { font-family: 'Playfair Display', serif; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; color: var(--cv-primary); margin-bottom: 10px; text-align: center; }
        .section h2::before, .section h2::after { content: '—'; margin: 0 8px; color: var(--cv-border); }
        .summary { font-size: 11px; line-height: 1.8; color: var(--cv-muted); text-align: center; max-width: 540px; margin: 0 auto; word-wrap: break-word; overflow-wrap: break-word; }
        .exp-item { margin-bottom: 14px; text-align: center; }
        .exp-row { display: flex; justify-content: center; gap: 16px; align-items: center; }
        .exp-title { font-size: 12px; font-weight: 600; }
        .exp-date { font-size: 10px; color: var(--cv-muted); font-style: italic; }
        .exp-company { font-size: 11px; color: var(--cv-accent); margin-bottom: 3px; }
        .exp-desc { font-size: 10.5px; line-height: 1.6; color: var(--cv-muted); text-align: center; max-width: 500px; margin: 0 auto; word-wrap: break-word; overflow-wrap: break-word; }
        .exp-desc ul { list-style: none; padding: 0; }
        .exp-desc li { word-wrap: break-word; }
        .exp-desc li::before { content: '· '; color: var(--cv-accent); }
        .edu-item { margin-bottom: 10px; text-align: center; }
        .edu-degree { font-size: 12px; font-weight: 600; }
        .edu-school { font-size: 11px; color: var(--cv-accent); }
        .edu-detail { font-size: 10px; color: var(--cv-muted); }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .skill-list { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
        .skill-item { font-size: 10px; color: var(--cv-muted); padding: 3px 0; }
        .skill-item::after { content: ' · '; color: var(--cv-border); }
        .skill-item:last-child::after { display: none; }
        .cert-item { font-size: 10.5px; margin-bottom: 3px; text-align: center; }
        .lang-item { display: flex; justify-content: space-between; font-size: 10.5px; margin-bottom: 3px; }
        .project-item { margin-bottom: 6px; text-align: center; }
        .project-name { font-size: 11px; font-weight: 600; }
        .project-desc { font-size: 10px; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        @if(!empty($photo_url))
            <img src="{{ $photo_url }}" alt="Photo" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:8px;" />
        @endif
        <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
        @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        <div class="divider"></div>
        <div class="contact-row">
            @if(!empty($candidate['email']))<span>{{ $candidate['email'] }}</span>@endif
            @if(!empty($candidate['phone']))<span>{{ $candidate['phone'] }}</span>@endif
            @if(!empty($candidate['location']))<span>{{ $candidate['location'] }}</span>@endif
            @if(!empty($candidate['website']))<span><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
            @if(!empty($candidate['date_of_birth']))<span>{{ $candidate['date_of_birth'] }}</span>@endif
            @if(!empty($candidate['gender']))<span>{{ $candidate['gender'] }}</span>@endif
            @if(!empty($candidate['nationality']))<span>{{ $candidate['nationality'] }}</span>@endif
            @if(!empty($candidate['father_name']))<span>F: {{ $candidate['father_name'] }}</span>@endif
            @if(!empty($candidate['mother_name']))<span>M: {{ $candidate['mother_name'] }}</span>@endif
            @if(!empty($candidate['religion']))<span>{{ $candidate['religion'] }}</span>@endif
            @if(!empty($candidate['blood_group']))<span>Blood: {{ $candidate['blood_group'] }}</span>@endif
            @if(!empty($candidate['marital_status']))<span>{{ $candidate['marital_status'] }}</span>@endif
        </div>
        @if(!empty($social_links) && count($social_links) > 0)
            <div style="margin-top:10px;">
                @foreach($social_links as $link)
                    @php $linkUrl = is_array($link) ? ($link['url'] ?? $link['link'] ?? '') : $link; $linkLabel = is_array($link) ? ($link['label'] ?? $link['platform'] ?? '') : ''; @endphp
                    @if($linkUrl)<div style="font-size:10px;color:var(--cv-muted);margin-bottom:2px;">@if($linkLabel)<span style="font-weight:600;">{{ $linkLabel }}:</span> @endif<a href="{{ $linkUrl }}" style="color:var(--cv-primary);text-decoration:none;">{{ $linkUrl }}</a></div>@endif
                @endforeach
            </div>
        @endif
    </div>
    <div class="content">
        @if(!empty($summary))
            <div class="section"><h2>Profile</h2><div class="summary">{{ $summary }}</div></div>
        @endif
        @if(!empty($experience) && count($experience) > 0)
            <div class="section">
                <h2>Experience</h2>
                @foreach($experience as $exp)
                    <div class="exp-item">
                        <div class="exp-row"><span class="exp-title">{{ $exp['position'] ?? $exp['title'] ?? '' }}</span><span class="exp-date">{{ $exp['start_date'] ?? '' }}{{ !empty($exp['end_date']) ? ' – ' . $exp['end_date'] : ' – Present' }}</span></div>
                        <div class="exp-company">{{ $exp['company'] ?? $exp['company_name'] ?? '' }}</div>
                        @if(!empty($exp['description']))<div class="exp-desc">@if(preg_match('/<[^>]+>/', $exp['description'])){!! $exp['description']!!}@else@php $lines = preg_split('/\r\n|\r|\n/', $exp['description']); $lines = array_filter(array_map('trim', $lines)); if (count($lines) <= 1 && str_contains($exp['description'], ',')) { $lines = array_filter(array_map('trim', explode(',', $exp['description']))); } if (count($lines) <= 1 && str_contains($exp['description'], ' • ')) { $lines = array_filter(array_map('trim', explode(' • ', $exp['description']))); } @endphp@if(count($lines) > 1)<ul>@foreach($lines as $line)<li>{{ $line }}</li>@endforeach</ul>@else<p>{{ $exp['description'] }}</p>@endif@endif</div>@endif
                    </div>
                @endforeach
            </div>
        @endif
        @if(!empty($education) && count($education) > 0)
            <div class="section">
                <h2>Education</h2>
                @foreach($education as $edu)<div class="edu-item"><div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div><div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div><div class="edu-detail">{{ $edu['start_date'] ?? '' }}@if(!empty($edu['end_date'])) – {{ $edu['end_date'] }}@endif@if(!empty($edu['grade'])) · {{ $edu['grade'] }}@elseif(!empty($edu['description'])) · {{ $edu['description'] }}@endif</div></div>@endforeach
            </div>
        @endif
        @if(!empty($skills) && count($skills) > 0)
            <div class="section">
                <h2>Skills</h2>
                <div class="skill-list">@foreach($skills as $skill)<span class="skill-item">{{ is_array($skill) ? ($skill['name'] ?? '') : $skill }}</span>@endforeach</div>
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
        @if(!empty($projects) && count($projects) > 0)
            <div class="section">
                <h2>Projects</h2>
                @foreach($projects as $p)<div class="project-item"><div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;"><div class="project-name">{{ $p['name'] ?? $p['title'] ?? '' }}</div>@if(!empty($p['url']))<a href="{{ $p['url'] }}" style="font-size:10px;color:var(--cv-primary);white-space:nowrap;text-decoration:none;">{{ $p['url'] }}</a>@endif</div>@if(!empty($p['technologies']))<div style="font-size:10px;color:var(--cv-primary);margin-bottom:3px;">{{ is_array($p['technologies']) ? implode(', ', $p['technologies']) : $p['technologies'] }}</div>@endif@if(!empty($p['description']))<div class="project-desc">{{ $p['description'] }}</div>@endif</div>@endforeach
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

        {{-- AWARDS --}}
        @if(!empty($awards) && count($awards) > 0)
            <div class="section">
                <h2>Awards & Honors</h2>
                @foreach($awards as $award)
                    <div class="cert-item"><span style="font-weight:600;">{{ is_array($award) ? ($award['name'] ?? '') : $award }}</span></div>
                @endforeach
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
