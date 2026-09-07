<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Corporate Clean</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --cv-primary: #1e3a8a; --cv-accent: #0d9488; --cv-text: #1e293b; --cv-bg: #ffffff; --cv-muted: #64748b; --cv-border: #e2e8f0; --cv-light: #f0fdfa; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--cv-text); background: #f1f5f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--cv-bg); box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        @media print { body { background: white; } .cv-page { box-shadow: none; margin: 0; width: 100%; } }
        .corp-bar {
            background: var(--cv-primary);
            padding: 24px 36px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        .corp-bar h1 {
            font-size: 24px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }
        .corp-bar .title {
            font-size: 11px;
            font-weight: 400;
            color: #93c5fd;
            margin-top: 2px;
        }
        .corp-contact {
            text-align: right;
            font-size: 9.5px;
            color: #bfdbfe;
            line-height: 1.8;
            flex-shrink: 0;
        }
        .corp-contact a { color: #99f6e4; text-decoration: none; }
        .corp-contact svg { width: 10px; height: 10px; fill: none; stroke: var(--cv-accent); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; display: inline; vertical-align: middle; }

        .corp-sub-bar {
            background: var(--cv-light);
            padding: 8px 36px;
            display: flex;
            gap: 20px;
            font-size: 9px;
            color: var(--cv-muted);
            border-bottom: 1px solid var(--cv-border);
        }
        .corp-sub-bar span { display: flex; align-items: center; gap: 4px; }
        .corp-sub-bar svg { width: 11px; height: 11px; fill: none; stroke: var(--cv-accent); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        .content { padding: 24px 36px; display: flex; flex-direction: column; gap: 18px; }

        .section h2 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--cv-primary);
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid transparent;
            border-image: linear-gradient(to right, var(--cv-primary), var(--cv-accent)) 1;
        }

        .summary-text {
            font-size: 11px;
            line-height: 1.7;
            color: var(--cv-muted);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .exp-item {
            margin-bottom: 12px;
            padding: 10px 14px;
            border: 1px solid var(--cv-border);
            border-radius: 6px;
            border-left: 3px solid var(--cv-accent);
        }
        .exp-row { display: flex; justify-content: space-between; align-items: flex-start; }
        .exp-title { font-size: 12px; font-weight: 700; color: var(--cv-primary); }
        .exp-date { font-size: 9.5px; color: var(--cv-muted); white-space: nowrap; }
        .exp-company { font-size: 11px; color: var(--cv-accent); font-weight: 600; margin: 2px 0 4px; }
        .exp-desc { font-size: 10.5px; line-height: 1.5; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .exp-desc ul { padding-left: 14px; }
        .exp-desc li { margin-bottom: 2px; word-wrap: break-word; }

        .edu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .edu-item {
            padding: 10px 12px;
            background: var(--cv-light);
            border-radius: 6px;
            border-bottom: 2px solid var(--cv-accent);
        }
        .edu-degree { font-size: 11.5px; font-weight: 700; color: var(--cv-primary); }
        .edu-school { font-size: 10.5px; color: var(--cv-accent); margin: 2px 0; }
        .edu-detail { font-size: 9.5px; color: var(--cv-muted); }

        .skills-wrap { display: flex; flex-wrap: wrap; gap: 6px; }
        .skill-tag {
            display: inline-block;
            background: var(--cv-light);
            border: 1px solid var(--cv-border);
            font-size: 9.5px;
            padding: 3px 10px;
            border-radius: 3px;
            color: var(--cv-muted);
        }

        .cert-item { font-size: 10.5px; margin-bottom: 4px; }
        .cert-name { font-weight: 600; color: var(--cv-primary); }

        .lang-row { display: flex; flex-wrap: wrap; gap: 14px; }
        .lang-item { font-size: 10px; }
        .lang-name { font-weight: 600; color: var(--cv-primary); }
        .lang-level { color: var(--cv-muted); }

        .project-item { margin-bottom: 8px; padding: 8px 10px; background: #fafafa; border-radius: 4px; }
        .project-name { font-size: 11px; font-weight: 600; color: var(--cv-primary); }
        .project-desc { font-size: 10px; color: var(--cv-muted); line-height: 1.5; margin-top: 2px; word-wrap: break-word; overflow-wrap: break-word; }

        .award-item { font-size: 10.5px; margin-bottom: 3px; color: var(--cv-muted); }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="corp-bar">
        <div>
            @if(!empty($photo_url))
                <img src="{{ $photo_url }}" alt="Photo" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:8px;" />
            @endif
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        </div>
        <div class="corp-contact">
            @if(!empty($candidate['email']))<div>{{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['phone']))<div>{{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['website']))<div><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></div>@endif
            @if(!empty($candidate['date_of_birth']))<div>{{ $candidate['date_of_birth'] }}</div>@endif
            @if(!empty($candidate['gender']))<div>{{ $candidate['gender'] }}</div>@endif
            @if(!empty($candidate['nationality']))<div>{{ $candidate['nationality'] }}</div>@endif
        </div>
    </div>
    <div class="corp-sub-bar">
        @if(!empty($candidate['location']))<span><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $candidate['location'] }}</span>@endif
        @if(!empty($candidate['phone']))<span><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>{{ $candidate['phone'] }}</span>@endif
    </div>

    <div class="content">
        @if(!empty($social_links) && count($social_links) > 0)
            <div class="section">
                <h2>Online Profiles</h2>
                @foreach($social_links as $link)
                    @php $linkUrl = is_array($link) ? ($link['url'] ?? $link['link'] ?? '') : $link; $linkLabel = is_array($link) ? ($link['label'] ?? $link['platform'] ?? '') : ''; @endphp
                    @if($linkUrl)<div class="cert-item">@if($linkLabel)<span class="cert-name">{{ $linkLabel }}:</span> @endif<a href="{{ $linkUrl }}" style="color:var(--cv-accent);text-decoration:none;">{{ $linkUrl }}</a></div>@endif
                @endforeach
            </div>
        @endif
        @if(!empty($summary))
            <div class="section"><h2>Professional Summary</h2><div class="summary-text">{{ $summary }}</div></div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
            <div class="section">
                <h2>Work Experience</h2>
                @foreach($experience as $exp)
                    <div class="exp-item">
                        <div class="exp-row">
                            <div class="exp-title">{{ $exp['position'] ?? $exp['title'] ?? '' }}</div>
                            <div class="exp-date">{{ $exp['start_date'] ?? '' }}{{ !empty($exp['end_date']) ? ' – ' . $exp['end_date'] : ' – Present' }}</div>
                        </div>
                        <div class="exp-company">{{ $exp['company'] ?? $exp['company_name'] ?? '' }}@if(!empty($exp['location'])) · {{ $exp['location'] }}@endif</div>
                        @if(!empty($exp['description']))
                            <div class="exp-desc">
                                @if(preg_match('/<[^>]+>/', $exp['description']))
                                    {!! $exp['description'] !!}
                                @else
                                    @php
                                        $lines = preg_split('/\r\n|\r|\n/', $exp['description']);
                                        $lines = array_filter(array_map('trim', $lines));
                                        if (count($lines) <= 1 && str_contains($exp['description'], ',')) {
                                            $lines = array_filter(array_map('trim', explode(',', $exp['description'])));
                                        }
                                        if (count($lines) <= 1 && str_contains($exp['description'], ' • ')) {
                                            $lines = array_filter(array_map('trim', explode(' • ', $exp['description'])));
                                        }
                                    @endphp
                                    @if(count($lines) > 1)
                                        <ul>
                                            @foreach($lines as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p>{{ $exp['description'] }}</p>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($education) && count($education) > 0)
            <div class="section">
                <h2>Education</h2>
                <div class="edu-grid">
                    @foreach($education as $edu)
                        <div class="edu-item">
                            <div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div>
                            <div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div>
                            <div class="edu-detail">{{ $edu['start_date'] ?? '' }}@if(!empty($edu['end_date'])) – {{ $edu['end_date'] }}@endif@if(!empty($edu['grade'])) · {{ $edu['grade'] }}@elseif(!empty($edu['description'])) · {{ $edu['description'] }}@endif</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
            <div class="section">
                <h2>Skills</h2>
                <div class="skills-wrap">
                    @foreach($skills as $skill)<span class="skill-tag">{{ is_array($skill) ? ($skill['name'] ?? $skill['label'] ?? '') : $skill }}</span>@endforeach
                </div>
            </div>
        @endif

        @if(!empty($certifications) && count($certifications) > 0)
            <div class="section">
                <h2>Certifications</h2>
                @foreach($certifications as $cert)
                    @php
                        $cn = is_array($cert) ? ($cert['name'] ?? $cert['title'] ?? '') : $cert;
                        $ci = is_array($cert) ? ($cert['issuer'] ?? $cert['organization'] ?? '') : '';
                        $certDate = is_array($cert) ? ($cert['date'] ?? '') : '';
                        if ($certDate) {
                            try { $certDate = \Carbon\Carbon::parse($certDate)->format('M Y'); } catch (\Exception $e) { $certDate = $certDate; }
                        }
                    @endphp
                    <div class="cert-item"><span class="cert-name">{{ $cn }}</span>@if($ci) — {{ $ci }}@endif@if($certDate) <span style="font-size:9px;color:var(--cv-muted);">({{ $certDate }})</span>@endif</div>
                @endforeach
            </div>
        @endif

        @if(!empty($projects) && count($projects) > 0)
            <div class="section">
                <h2>Projects</h2>
                @foreach($projects as $p)
                    <div class="project-item">
                        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;">
                            <div class="project-name">{{ $p['name'] ?? $p['title'] ?? '' }}</div>
                            @if(!empty($p['url']))
                                <a href="{{ $p['url'] }}" style="font-size:10px;color:var(--cv-primary);white-space:nowrap;text-decoration:none;">{{ $p['url'] }}</a>
                            @endif
                        </div>
                        @if(!empty($p['technologies']))
                            <div style="font-size:10px;color:var(--cv-primary);margin-bottom:3px;">{{ is_array($p['technologies']) ? implode(', ', $p['technologies']) : $p['technologies'] }}</div>
                        @endif
                        @if(!empty($p['description']))
                            <div class="project-desc">{{ $p['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
            <div class="section">
                <h2>Languages</h2>
                <div class="lang-row">
                    @foreach($languages as $lang)
                        @php $ln = is_array($lang) ? ($lang['name'] ?? $lang['language'] ?? '') : $lang; $ll = is_array($lang) ? ($lang['proficiency'] ?? $lang['level'] ?? '') : ''; @endphp
                        <div class="lang-item"><span class="lang-name">{{ $ln }}</span>@if($ll)<span class="lang-level"> ({{ $ll }})</span>@endif</div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!empty($awards) && count($awards) > 0)
            <div class="section">
                <h2>Awards</h2>
                @foreach($awards as $award)
                    <div class="award-item">{{ is_array($award) ? ($award['name'] ?? $award['title'] ?? '') : $award }}</div>
                @endforeach
            </div>
        @endif

        @if(!empty($training) && count($training) > 0)
            <div class="section">
                <h2>Training</h2>
                @foreach($training as $t)
                    <div class="cert-item"><span class="cert-name">{{ $t['title'] ?? '' }}</span>@if(!empty($t['institute'])) — {{ $t['institute'] }}@endif@if(!empty($t['duration'])) <span style="font-size:9px;color:var(--cv-muted);">({{ $t['duration'] }})</span>@endif</div>
                @endforeach
            </div>
        @endif

        @if(!empty($references) && count($references) > 0)
            <div class="section">
                <h2>References</h2>
                @foreach($references as $ref)
                    <div class="cert-item"><span class="cert-name">{{ $ref['name'] ?? '' }}</span>@if(!empty($ref['designation'])) — {{ $ref['designation'] }}@endif@if(!empty($ref['organization']))<br/><span style="font-size:9.5px;color:var(--cv-muted);">{{ $ref['organization'] }}</span>@endif</div>
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