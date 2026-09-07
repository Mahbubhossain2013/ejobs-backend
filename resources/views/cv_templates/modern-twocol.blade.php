<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Modern Two-Column</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --cv-primary: #0f172a; --cv-accent: #06b6d4; --cv-text: #1e293b; --cv-bg: #ffffff; --cv-muted: #64748b; --cv-border: #e2e8f0; --cv-sidebar: #0f172a; --cv-hero-end: #0891b2; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--cv-text); background: #f1f5f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: var(--cv-bg); box-shadow: 0 4px 24px rgba(0,0,0,0.08); display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: white; } .cv-page { box-shadow: none; margin: 0; width: 100%; } }
        .hero {
            background: linear-gradient(135deg, var(--cv-sidebar), var(--cv-accent), var(--cv-hero-end));
            padding: 32px 36px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .hero-inner { position: relative; z-index: 1; }
        .hero-row { display: flex; align-items: center; gap: 20px; }
        .hero-photo {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            overflow: hidden;
            border: 3px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
            transform: rotate(-3deg);
        }
        .hero-photo img { width: 100%; height: 100%; object-fit: cover; }
        .hero-fallback {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.15);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 30px;
            font-weight: 700;
        }
        .hero-text { flex: 1; }
        .hero-text h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .hero-text .title {
            font-size: 12px;
            font-weight: 400;
            opacity: 0.85;
            margin-top: 2px;
        }
        .hero-contact {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 18px;
            margin-top: 10px;
            font-size: 9.5px;
            opacity: 0.85;
        }
        .hero-contact span { display: flex; align-items: center; gap: 5px; }
        .hero-contact svg { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .hero-contact a { color: white; text-decoration: underline; text-underline-offset: 2px; }

        .body-wrap { display: flex; flex: 1; }
        .left-panel {
            width: 33%;
            background: #f8fafc;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            border-right: 1px solid var(--cv-border);
        }
        .left-panel .section-label {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--cv-accent);
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px dashed var(--cv-border);
        }

        .skill-item {
            display: inline-block;
            font-size: 9.5px;
            background: white;
            border: 1px solid var(--cv-border);
            padding: 3px 9px;
            border-radius: 3px;
            margin: 0 3px 5px 0;
            color: var(--cv-muted);
        }

        .lang-item { display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 4px; }
        .lang-name { font-weight: 500; }
        .lang-level { color: var(--cv-muted); }

        .contact-block-item {
            font-size: 10px;
            margin-bottom: 6px;
            color: var(--cv-muted);
            word-break: break-all;
        }
        .contact-block-item a { color: var(--cv-accent); text-decoration: none; }

        .social-item { font-size: 9.5px; margin-bottom: 4px; }
        .social-item a { color: var(--cv-accent); text-decoration: none; }

        .right-panel {
            flex: 1;
            padding: 24px 26px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .right-panel .section-label {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--cv-primary);
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid var(--cv-accent);
            display: inline-block;
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
            padding: 10px 12px;
            border-radius: 6px;
            background: #f8fafc;
        }
        .exp-row { display: flex; justify-content: space-between; align-items: flex-start; }
        .exp-title { font-size: 12px; font-weight: 700; color: var(--cv-primary); }
        .exp-date { font-size: 9.5px; color: var(--cv-muted); white-space: nowrap; }
        .exp-company { font-size: 11px; color: var(--cv-accent); font-weight: 500; margin: 2px 0 4px; }
        .exp-desc { font-size: 10.5px; line-height: 1.5; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .exp-desc ul { padding-left: 14px; }
        .exp-desc li { margin-bottom: 1px; word-wrap: break-word; }

        .edu-item { margin-bottom: 8px; }
        .edu-degree { font-size: 11.5px; font-weight: 600; color: var(--cv-primary); }
        .edu-school { font-size: 10.5px; color: var(--cv-accent); }
        .edu-detail { font-size: 10px; color: var(--cv-muted); }

        .cert-item { font-size: 10.5px; margin-bottom: 4px; }
        .cert-name { font-weight: 600; }
        .project-item { margin-bottom: 8px; }
        .project-name { font-size: 11px; font-weight: 600; color: var(--cv-primary); }
        .project-desc { font-size: 10px; color: var(--cv-muted); word-wrap: break-word; overflow-wrap: break-word; }
        .award-item { font-size: 10.5px; margin-bottom: 3px; color: var(--cv-muted); }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="hero">
        <div class="hero-inner">
            <div class="hero-row">
                @if(!empty($candidate['photo_url']) || !empty($candidate['full_name']))
                    <div class="hero-photo">
                        @if(!empty($candidate['photo_url']))
                            <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
                        @else
                            <div class="hero-fallback">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
                        @endif
                    </div>
                @endif
                <div class="hero-text">
                    <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
                    @if(!empty($candidate['title']))<div class="title">{{ $candidate['title'] }}</div>@endif
                    <div class="hero-contact">
                        @if(!empty($candidate['email']))<span><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>{{ $candidate['email'] }}</span>@endif
                        @if(!empty($candidate['phone']))<span><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>{{ $candidate['phone'] }}</span>@endif
                        @if(!empty($candidate['location']))<span><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $candidate['location'] }}</span>@endif
                        @if(!empty($candidate['website']))<span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
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
        </div>
    </div>

    <div class="body-wrap">
        <div class="left-panel">
            @if(!empty($skills) && count($skills) > 0)
                <div>
                    <div class="section-label">Skills</div>
                    <div>@foreach($skills as $skill)<span class="skill-item">{{ is_array($skill) ? ($skill['name'] ?? $skill['label'] ?? '') : $skill }}</span>@endforeach</div>
                </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
                <div>
                    <div class="section-label">Languages</div>
                    @foreach($languages as $lang)
                        @php $ln = is_array($lang) ? ($lang['name'] ?? $lang['language'] ?? '') : $lang; $ll = is_array($lang) ? ($lang['proficiency'] ?? $lang['level'] ?? '') : ''; @endphp
                        <div class="lang-item"><span class="lang-name">{{ $ln }}</span><span class="lang-level">{{ $ll }}</span></div>
                    @endforeach
                </div>
            @endif

            @if(!empty($certifications) && count($certifications) > 0)
                <div>
                    <div class="section-label">Certifications</div>
                    @foreach($certifications as $cert)
                        @php
                            $cn = is_array($cert) ? ($cert['name'] ?? $cert['title'] ?? '') : $cert;
                            $ci = is_array($cert) ? ($cert['issuer'] ?? $cert['organization'] ?? '') : '';
                            $certDate = is_array($cert) ? ($cert['date'] ?? '') : '';
                            if ($certDate) {
                                try { $certDate = \Carbon\Carbon::parse($certDate)->format('M Y'); } catch (\Exception $e) { $certDate = $certDate; }
                            }
                        @endphp
                        <div class="cert-item"><span class="cert-name">{{ $cn }}</span>@if($ci)<br><span style="font-size:9px;color:var(--cv-muted)">{{ $ci }}</span>@endif@if($certDate)<br><span style="font-size:9px;color:var(--cv-muted);">({{ $certDate }})</span>@endif</div>
                    @endforeach
                </div>
            @endif

            @if(!empty($social_links) && count($social_links) > 0)
                <div>
                    <div class="section-label">Online</div>
                    @foreach($social_links as $link)
                        @php $lu = is_array($link) ? ($link['url'] ?? $link['link'] ?? '') : $link; $ll2 = is_array($link) ? ($link['label'] ?? $link['platform'] ?? '') : ''; @endphp
                        @if($lu)<div class="social-item">@if($ll2)<strong>{{ $ll2 }}:</strong> @endif<a href="{{ $lu }}">{{ $lu }}</a></div>@endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="right-panel">
            @if(!empty($summary))
                <div>
                    <div class="section-label">About</div>
                    <div class="summary-text">{{ $summary }}</div>
                </div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
                <div>
                    <div class="section-label">Experience</div>
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
                <div>
                    <div class="section-label">Education</div>
                    @foreach($education as $edu)
                        <div class="edu-item">
                            <div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div>
                            <div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div>
                            <div class="edu-detail">{{ $edu['start_date'] ?? '' }}@if(!empty($edu['end_date'])) – {{ $edu['end_date'] }}@endif@if(!empty($edu['grade'])) · {{ $edu['grade'] }}@elseif(!empty($edu['description'])) · {{ $edu['description'] }}@endif</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!empty($training) && count($training) > 0)
                <div>
                    <div class="section-label">Training</div>
                    @foreach($training as $t)
                        <div class="cert-item"><span class="cert-name">{{ $t['title'] ?? '' }}</span>@if(!empty($t['institute']))<br><span style="font-size:9px;color:var(--cv-muted)">{{ $t['institute'] }}</span>@endif@if(!empty($t['duration']))<br><span style="font-size:9px;color:var(--cv-muted);">({{ $t['duration'] }})</span>@endif</div>
                    @endforeach
                </div>
            @endif

            @if(!empty($projects) && count($projects) > 0)
                <div>
                    <div class="section-label">Projects</div>
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

            @if(!empty($awards) && count($awards) > 0)
                <div>
                    <div class="section-label">Awards</div>
                    @foreach($awards as $award)
                        <div class="award-item">{{ is_array($award) ? ($award['name'] ?? $award['title'] ?? '') : $award }}</div>
                    @endforeach
                </div>
            @endif

            @if(!empty($references) && count($references) > 0)
                <div>
                    <div class="section-label">References</div>
                    @foreach($references as $ref)
                        <div class="cert-item"><span class="cert-name">{{ $ref['name'] ?? '' }}</span>@if(!empty($ref['designation'])) — {{ $ref['designation'] }}@endif@if(!empty($ref['organization']))<br><span style="font-size:9px;color:var(--cv-muted);">{{ $ref['organization'] }}</span>@endif</div>
                    @endforeach
                </div>
            @endif

            {{-- HOBBIES --}}
            @if(!empty($hobbies) && count($hobbies) > 0)
                <div>
                    <div class="section-label">Hobbies & Interests</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($hobbies as $hobby)
                            <span style="font-size:10px;background:rgba(0,0,0,0.04);padding:3px 10px;border-radius:3px;">
                                {{ is_array($hobby) ? ($hobby['name'] ?? $hobby['title'] ?? '') : $hobby }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>