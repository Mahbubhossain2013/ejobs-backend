<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Bold Professional</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --cv-primary: #0f0f0f; --cv-accent: #2563eb; --cv-text: #0f0f0f; --cv-bg: #ffffff; --cv-muted: #6b7280; --cv-border: #e5e7eb; --cv-card-bg: #f9fafb; --cv-dark-card: #1a1a2e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--cv-text); background: #f1f5f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--cv-bg); box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        @media print { body { background: white; } .cv-page { box-shadow: none; margin: 0; width: 100%; } }
        .bold-header {
            background: var(--cv-primary);
            padding: 28px 36px 24px;
            position: relative;
        }
        .bold-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--cv-accent);
        }
        .bold-header h1 {
            font-family: 'DM Sans', sans-serif;
            font-size: 34px;
            font-weight: 900;
            color: white;
            letter-spacing: -1px;
            line-height: 1.1;
        }
        .bold-header .title {
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 400;
            color: #93a3b8;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }
        .bold-contact {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 20px;
            margin-top: 12px;
            font-size: 10px;
            color: #9ca3af;
        }
        .bold-contact span { display: flex; align-items: center; gap: 5px; }
        .bold-contact svg { width: 12px; height: 12px; fill: none; stroke: var(--cv-accent); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .bold-contact a { color: #60a5fa; text-decoration: none; }

        .content { padding: 24px 36px; display: flex; flex-direction: column; gap: 20px; }

        .section {
            position: relative;
        }
        .section h2 {
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--cv-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section h2::after {
            content: '';
            flex: 1;
            height: 3px;
            background: var(--cv-accent);
        }

        .summary-text {
            font-size: 11px;
            line-height: 1.7;
            color: var(--cv-muted);
            padding: 10px 14px;
            background: var(--cv-card-bg);
            border-radius: 6px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .skills-grid { display: flex; flex-wrap: wrap; gap: 6px; }
        .skill-chip {
            background: var(--cv-primary);
            color: white;
            font-size: 9.5px;
            padding: 5px 14px;
            font-weight: 500;
            border-radius: 4px;
            letter-spacing: 0.3px;
        }

        .exp-item {
            margin-bottom: 12px;
            padding: 12px 16px;
            background: var(--cv-card-bg);
            border-radius: 8px;
            border-left: 4px solid var(--cv-accent);
        }
        .exp-row { display: flex; justify-content: space-between; align-items: flex-start; }
        .exp-title { font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 700; color: var(--cv-primary); }
        .exp-date { font-size: 9.5px; color: var(--cv-muted); white-space: nowrap; margin-top: 2px; }
        .exp-company { font-size: 11px; color: var(--cv-accent); font-weight: 600; margin: 3px 0 4px; }
        .exp-desc { font-size: 10.5px; line-height: 1.5; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .exp-desc ul { padding-left: 14px; }
        .exp-desc li { margin-bottom: 2px; word-wrap: break-word; }

        .edu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .edu-card {
            padding: 12px 14px;
            background: var(--cv-card-bg);
            border-radius: 8px;
            border-top: 3px solid var(--cv-accent);
        }
        .edu-degree { font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 700; color: var(--cv-primary); }
        .edu-school { font-size: 10.5px; color: var(--cv-accent); margin: 2px 0; }
        .edu-detail { font-size: 9.5px; color: var(--cv-muted); }

        .cert-item { font-size: 10.5px; margin-bottom: 5px; padding: 4px 10px; background: var(--cv-card-bg); border-radius: 4px; }
        .cert-name { font-weight: 600; color: var(--cv-primary); }
        .cert-issuer { color: var(--cv-muted); }

        .project-item { margin-bottom: 8px; padding: 8px 12px; background: var(--cv-card-bg); border-radius: 6px; }
        .project-name { font-size: 11px; font-weight: 700; color: var(--cv-primary); }
        .project-desc { font-size: 10px; color: var(--cv-muted); line-height: 1.5; margin-top: 2px; word-wrap: break-word; overflow-wrap: break-word; }

        .lang-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .lang-chip {
            font-size: 10px;
            padding: 4px 12px;
            border: 1px solid var(--cv-border);
            border-radius: 20px;
            background: white;
        }
        .lang-name { font-weight: 600; }
        .lang-level { color: var(--cv-muted); }

        .award-item { font-size: 10.5px; margin-bottom: 4px; padding: 4px 10px; background: var(--cv-card-bg); border-radius: 4px; }

        .two-col-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="bold-header">
        @if(!empty($photo_url))
            <img src="{{ $photo_url }}" alt="Photo" style="width:80px;height:80px;border-radius:50%;object-fit:cover;" />
        @endif
        <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
        @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
        <div class="bold-contact">
            @if(!empty($candidate['email']))<span><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>{{ $candidate['email'] }}</span>@endif
            @if(!empty($candidate['phone']))<span><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>{{ $candidate['phone'] }}</span>@endif
            @if(!empty($candidate['location']))<span><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $candidate['location'] }}</span>@endif
            @if(!empty($candidate['website']))<span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
            @if(!empty($candidate['date_of_birth']))<span><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>{{ $candidate['date_of_birth'] }}</span>@endif
            @if(!empty($candidate['gender']))<span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>{{ $candidate['gender'] }}</span>@endif
            @if(!empty($candidate['nationality']))<span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>{{ $candidate['nationality'] }}</span>@endif
            @if(!empty($candidate['father_name']))<span><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Father: {{ $candidate['father_name'] }}</span>@endif
            @if(!empty($candidate['mother_name']))<span><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Mother: {{ $candidate['mother_name'] }}</span>@endif
            @if(!empty($candidate['religion']))<span><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>{{ $candidate['religion'] }}</span>@endif
            @if(!empty($candidate['blood_group']))<span><svg viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>Blood: {{ $candidate['blood_group'] }}</span>@endif
            @if(!empty($candidate['marital_status']))<span><svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>{{ $candidate['marital_status'] }}</span>@endif
        </div>
        @if(!empty($social_links) && count($social_links) > 0)
            <div style="margin-top:12px;">
                @foreach($social_links as $link)
                    @php $linkUrl = is_array($link) ? ($link['url'] ?? $link['link'] ?? '') : $link; $linkLabel = is_array($link) ? ($link['label'] ?? $link['platform'] ?? '') : ''; @endphp
                    @if($linkUrl)<div style="font-size:10px;color:#9ca3af;margin-bottom:3px;">@if($linkLabel)<span style="color:var(--cv-accent);">{{ $linkLabel }}:</span> @endif<a href="{{ $linkUrl }}">{{ $linkUrl }}</a></div>@endif
                @endforeach
            </div>
        @endif
    </div>

    <div class="content">
        @if(!empty($summary))
            <div class="section"><h2>Summary</h2><div class="summary-text">{{ $summary }}</div></div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
            <div class="section">
                <h2>Technical Skills</h2>
                <div class="skills-grid">@foreach($skills as $skill)<span class="skill-chip">{{ is_array($skill) ? ($skill['name'] ?? $skill['label'] ?? '') : $skill }}</span>@endforeach</div>
            </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
            <div class="section">
                <h2>Experience</h2>
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
                        <div class="edu-card">
                            <div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div>
                            <div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div>
                            <div class="edu-detail">{{ $edu['start_date'] ?? '' }}@if(!empty($edu['end_date'])) – {{ $edu['end_date'] }}@endif@if(!empty($edu['grade'])) · {{ $edu['grade'] }}@elseif(!empty($edu['description'])) · {{ $edu['description'] }}@endif</div>
                        </div>
                    @endforeach
                </div>
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
                    <div class="cert-item"><span class="cert-name">{{ $cn }}</span>@if($ci) <span class="cert-issuer">— {{ $ci }}</span>@endif@if($certDate) <span style="font-size:9px;color:var(--cv-muted);">({{ $certDate }})</span>@endif</div>
                @endforeach
            </div>
        @endif

        @if(!empty($training) && count($training) > 0)
            <div class="section">
                <h2>Training</h2>
                @foreach($training as $t)
                    <div class="cert-item">
                        <span class="cert-name">{{ $t['title'] ?? '' }}</span>
                        @if(!empty($t['institute'])) <span class="cert-issuer"> — {{ $t['institute'] }}</span> @endif
                        @if(!empty($t['duration'])) <span class="cert-issuer"> ({{ $t['duration'] }})</span> @endif
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
                        <span class="lang-chip"><span class="lang-name">{{ $ln }}</span>@if($ll)<span class="lang-level"> ({{ $ll }})</span>@endif</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if(!empty($awards) && count($awards) > 0)
            <div class="section">
                <h2>Awards & Recognition</h2>
                @foreach($awards as $award)
                    <div class="award-item">{{ is_array($award) ? ($award['name'] ?? $award['title'] ?? '') : $award }}</div>
                @endforeach
            </div>
        @endif

        @if(!empty($references) && count($references) > 0)
            <div class="section">
                <h2>References</h2>
                @foreach($references as $ref)
                    <div class="cert-item">
                        <span class="cert-name">{{ $ref['name'] ?? '' }}</span>
                        @if(!empty($ref['designation'])) <span class="cert-issuer"> — {{ $ref['designation'] }}</span> @endif
                        @if(!empty($ref['organization']))<br/><span class="cert-issuer">{{ $ref['organization'] }}</span> @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- HOBBIES --}}
        @if(!empty($hobbies) && count($hobbies) > 0)
            <div class="section">
                <h2>Hobbies & Interests</h2>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($hobbies as $hobby)
                        <span style="font-size:11px;background:rgba(30,58,95,0.06);padding:3px 10px;border-radius:4px;color:var(--cv-text);">
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