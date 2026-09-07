<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Executive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --cv-primary: #0a1628; --cv-accent: #c9a94e; --cv-text: #1e293b; --cv-bg: #ffffff; --cv-muted: #64748b; --cv-border: #e2e8f0; --cv-light-bg: #f8f6f0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--cv-text); background: #f1f5f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--cv-bg); box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        @media print { body { background: white; } .cv-page { box-shadow: none; margin: 0; width: 100%; } }
        .exec-header {
            background: var(--cv-primary);
            color: white;
            padding: 32px 40px 28px;
            position: relative;
        }
        .exec-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 40px;
            right: 40px;
            height: 3px;
            background: var(--cv-accent);
        }
        .exec-name {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .exec-title {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 300;
            color: #94a3b8;
            margin-top: 4px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .exec-contact {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 24px;
            margin-top: 14px;
            font-size: 10px;
            color: #cbd5e1;
        }
        .exec-contact span { display: flex; align-items: center; gap: 6px; }
        .exec-contact svg { width: 12px; height: 12px; fill: none; stroke: var(--cv-accent); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
        .exec-contact a { color: var(--cv-accent); text-decoration: none; }

        .content { padding: 28px 40px; display: flex; flex-direction: column; gap: 20px; }

        .section { }
        .section-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 10px;
        }
        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--cv-primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        .section-header .rule {
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--cv-accent), transparent);
        }

        .summary-text {
            font-size: 11px;
            line-height: 1.7;
            color: var(--cv-muted);
            padding-left: 14px;
            border-left: 2px solid var(--cv-accent);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .exp-item {
            margin-bottom: 14px;
            padding: 12px 14px;
            background: var(--cv-light-bg);
            border-radius: 6px;
            border-left: 3px solid var(--cv-accent);
        }
        .exp-row { display: flex; justify-content: space-between; align-items: flex-start; }
        .exp-title { font-size: 12px; font-weight: 700; color: var(--cv-primary); }
        .exp-date { font-size: 9.5px; color: var(--cv-muted); white-space: nowrap; margin-top: 1px; }
        .exp-company { font-size: 11px; color: var(--cv-accent); font-weight: 600; margin: 2px 0 4px; }
        .exp-desc { font-size: 10.5px; line-height: 1.5; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .exp-desc ul { padding-left: 14px; list-style: none; }
        .exp-desc li { margin-bottom: 2px; position: relative; padding-left: 12px; word-wrap: break-word; }
        .exp-desc li::before { content: '\25B8'; position: absolute; left: 0; color: var(--cv-accent); font-size: 9px; }

        .edu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .edu-card {
            background: var(--cv-light-bg);
            padding: 10px 14px;
            border-radius: 6px;
            border-top: 2px solid var(--cv-accent);
        }
        .edu-degree { font-size: 12px; font-weight: 700; color: var(--cv-primary); }
        .edu-school { font-size: 10.5px; color: var(--cv-accent); margin: 2px 0; }
        .edu-detail { font-size: 9.5px; color: var(--cv-muted); }

        .skills-wrap { display: flex; flex-wrap: wrap; gap: 6px; }
        .skill-pill {
            background: var(--cv-primary);
            color: white;
            font-size: 9.5px;
            padding: 4px 12px;
            border-radius: 3px;
            font-weight: 500;
        }

        .cert-item { font-size: 10.5px; margin-bottom: 4px; padding-left: 14px; position: relative; }
        .cert-item::before { content: '\2713'; position: absolute; left: 0; color: var(--cv-accent); font-size: 10px; font-weight: 700; }

        .lang-row { display: flex; gap: 16px; flex-wrap: wrap; }
        .lang-item { font-size: 10.5px; }
        .lang-name { font-weight: 600; }
        .lang-level { color: var(--cv-muted); }

        .project-item { margin-bottom: 8px; }
        .project-name { font-size: 11px; font-weight: 600; color: var(--cv-primary); }
        .project-desc { font-size: 10px; color: var(--cv-muted); line-height: 1.5; word-wrap: break-word; overflow-wrap: break-word; }

        .award-item { font-size: 10.5px; margin-bottom: 4px; padding-left: 14px; position: relative; }
        .award-item::before { content: '\2605'; position: absolute; left: 0; color: var(--cv-accent); font-size: 10px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="exec-header">
        @if(!empty($photo_url))
            <img src="{{ $photo_url }}" alt="Photo" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:10px;" />
        @endif
        <div class="exec-name">{{ $candidate['full_name'] ?? 'Your Name' }}</div>
        @if(!empty($candidate['title']))<div class="exec-title">{{ $candidate['title'] }}</div>@endif
        <div class="exec-contact">
            @if(!empty($candidate['email']))<span><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>{{ $candidate['email'] }}</span>@endif
            @if(!empty($candidate['phone']))<span><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>{{ $candidate['phone'] }}</span>@endif
            @if(!empty($candidate['location']))<span><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $candidate['location'] }}</span>@endif
            @if(!empty($candidate['website']))<span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
            @if(!empty($candidate['date_of_birth']))<span>{{ $candidate['date_of_birth'] }}</span>@endif
            @if(!empty($candidate['gender']))<span>{{ $candidate['gender'] }}</span>@endif
            @if(!empty($candidate['nationality']))<span>{{ $candidate['nationality'] }}</span>@endif
            @if(!empty($candidate['father_name']))<span>Father: {{ $candidate['father_name'] }}</span>@endif
            @if(!empty($candidate['mother_name']))<span>Mother: {{ $candidate['mother_name'] }}</span>@endif
            @if(!empty($candidate['religion']))<span>{{ $candidate['religion'] }}</span>@endif
            @if(!empty($candidate['blood_group']))<span>Blood: {{ $candidate['blood_group'] }}</span>@endif
            @if(!empty($candidate['marital_status']))<span>{{ $candidate['marital_status'] }}</span>@endif
        </div>
        @if(!empty($social_links) && count($social_links) > 0)
            <div style="margin-top:14px;">
                @foreach($social_links as $link)
                    @php $linkUrl = is_array($link) ? ($link['url'] ?? $link['link'] ?? '') : $link; $linkLabel = is_array($link) ? ($link['label'] ?? $link['platform'] ?? '') : ''; @endphp
                    @if($linkUrl)<div style="font-size:10px;color:#cbd5e1;margin-bottom:3px;">@if($linkLabel)<span style="color:var(--cv-accent);">{{ $linkLabel }}:</span> @endif<a href="{{ $linkUrl }}" style="color:var(--cv-accent);text-decoration:none;">{{ $linkUrl }}</a></div>@endif
                @endforeach
            </div>
        @endif
    </div>

    <div class="content">
        @if(!empty($summary))
            <div class="section">
                <div class="section-header"><h2>Executive Summary</h2><div class="rule"></div></div>
                <div class="summary-text">{{ $summary }}</div>
            </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
            <div class="section">
                <div class="section-header"><h2>Career History</h2><div class="rule"></div></div>
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
                <div class="section-header"><h2>Education</h2><div class="rule"></div></div>
                <div class="edu-grid">
                    @foreach($education as $edu)
                        <div class="edu-card">
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
                <div class="section-header"><h2>Core Competencies</h2><div class="rule"></div></div>
                <div class="skills-wrap">
                    @foreach($skills as $skill)<span class="skill-pill">{{ is_array($skill) ? ($skill['name'] ?? $skill['label'] ?? '') : $skill }}</span>@endforeach
                </div>
            </div>
        @endif

        @if(!empty($certifications) && count($certifications) > 0)
            <div class="section">
                <div class="section-header"><h2>Certifications</h2><div class="rule"></div></div>
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
                <div class="section-header"><h2>Projects</h2><div class="rule"></div></div>
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
                <div class="section-header"><h2>Languages</h2><div class="rule"></div></div>
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
                <div class="section-header"><h2>Awards</h2><div class="rule"></div></div>
                @foreach($awards as $award)
                    <div class="award-item">{{ is_array($award) ? ($award['name'] ?? $award['title'] ?? '') : $award }}</div>
                @endforeach
            </div>
        @endif

        @if(!empty($training) && count($training) > 0)
            <div class="section">
                <div class="section-header"><h2>Training</h2><div class="rule"></div></div>
                @foreach($training as $t)
                    <div class="cert-item"><span style="font-weight:600;">{{ $t['title'] ?? '' }}</span>@if(!empty($t['institute'])) — {{ $t['institute'] }}@endif@if(!empty($t['duration'])) <span style="font-size:9px;color:var(--cv-muted);">({{ $t['duration'] }})</span>@endif</div>
                @endforeach
            </div>
        @endif

        @if(!empty($references) && count($references) > 0)
            <div class="section">
                <div class="section-header"><h2>References</h2><div class="rule"></div></div>
                @foreach($references as $ref)
                    <div class="cert-item"><span style="font-weight:600;">{{ $ref['name'] ?? '' }}</span>@if(!empty($ref['designation'])) — {{ $ref['designation'] }}@endif@if(!empty($ref['organization']))<br/><span style="font-size:9.5px;color:var(--cv-muted);">{{ $ref['organization'] }}</span>@endif</div>
                @endforeach
            </div>
        @endif

        {{-- HOBBIES --}}
        @if(!empty($hobbies) && count($hobbies) > 0)
            <div class="section">
                <div class="section-header"><h2>Hobbies & Interests</h2><div class="rule"></div></div>
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