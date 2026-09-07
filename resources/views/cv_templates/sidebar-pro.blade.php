<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Sidebar Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --cv-primary: var(--primary-color, #1e3a5f);
            --cv-text: var(--text-color, #1e293b);
            --cv-bg: var(--bg-color, #ffffff);
            --cv-muted: #64748b;
            --cv-border: #e2e8f0;
            --cv-sidebar-bg: #1e293b;
            --cv-sidebar-text: #e2e8f0;
            --cv-sidebar-muted: #94a3b8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--cv-text); background: #f1f5f9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        /* ── Full-page sidebar background trick ──────────────────────────────
           Paints the sidebar colour across the left 35% of every printed page
           so the sidebar fill covers the entire page even when content is short.
        ─────────────────────────────────────────────────────────────────────── */
        @media print {
            html, body {
                background: linear-gradient(to right,
                    var(--cv-sidebar-bg) 35%,
                    #ffffff 35%
                ) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        .cv-page {
            width: 210mm;
            min-height: auto;
            margin: 0 auto;
            display: flex;
            background: var(--cv-bg);
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            align-items: stretch;
        }
        @media print {
            body { background: white; }
            .cv-page { box-shadow: none; margin: 0; width: 100%; min-height: auto; }
        }
        /* Sidebar */
        .sidebar {
            width: 35%;
            min-width: 250px;
            background: var(--cv-sidebar-bg);
            color: var(--cv-sidebar-text);
            padding: 36px 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            align-self: stretch;
            min-height: 100%;
        }
        .sidebar-section h3 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--cv-sidebar-muted);
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* Photo */
        .photo-section { text-align: center; padding-bottom: 8px; }
        .photo-wrap {
            width: 120px;
            height: 120px;
            margin: 0 auto 12px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255,255,255,0.2);
        }
        .photo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--cv-primary), #3b82f6);
            color: white;
            font-size: 36px;
            font-weight: 700;
        }

        /* Contact */
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            margin-bottom: 8px;
            color: var(--cv-sidebar-text);
            word-break: break-all;
        }
        .contact-item svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            fill: none;
            stroke: var(--cv-sidebar-muted);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .contact-item a { color: var(--cv-sidebar-text); text-decoration: none; }

        /* Skills */
        .skill-tag {
            display: inline-block;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--cv-sidebar-text);
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 4px;
            margin: 0 4px 6px 0;
        }

        /* Languages */
        .lang-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            margin-bottom: 6px;
        }
        .lang-dots { display: flex; gap: 4px; }
        .lang-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
        }
        .lang-dot.filled { background: #3b82f6; }

        /* Main */
        .main-content {
            flex: 1;
            padding: 36px 28px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            justify-content: flex-start;
        }
        .main-section h2 {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--cv-primary);
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--cv-primary);
        }

        /* Header */
        .cv-header h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--cv-text);
            line-height: 1.2;
        }
        .cv-header .title {
            font-size: 13px;
            font-weight: 500;
            color: var(--cv-muted);
            margin-top: 4px;
        }

        /* Summary */
        .summary-text {
            font-size: 11px;
            line-height: 1.6;
            color: var(--cv-muted);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Experience */
        .exp-item {
            margin-bottom: 14px;
            position: relative;
            padding-left: 14px;
        }
        .exp-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 4px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--cv-primary);
        }
        .exp-item::after {
            content: '';
            position: absolute;
            left: 2.5px;
            top: 12px;
            width: 1px;
            height: calc(100% - 4px);
            background: var(--cv-border);
        }
        .exp-item:last-child::after { display: none; }
        .exp-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2px;
        }
        .exp-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--cv-text);
        }
        .exp-date {
            font-size: 10px;
            color: var(--cv-muted);
            white-space: nowrap;
        }
        .exp-company {
            font-size: 11px;
            font-weight: 500;
            color: var(--cv-primary);
            margin-bottom: 4px;
        }
        .exp-desc {
            font-size: 10.5px;
            line-height: 1.5;
            color: var(--cv-muted);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .exp-desc ul { padding-left: 14px; }
        .exp-desc li { margin-bottom: 2px; word-wrap: break-word; }

        /* Education */
        .edu-item {
            margin-bottom: 10px;
            padding-left: 14px;
            position: relative;
        }
        .edu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 4px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--cv-primary);
        }
        .edu-degree {
            font-size: 12px;
            font-weight: 600;
            color: var(--cv-text);
        }
        .edu-school {
            font-size: 11px;
            color: var(--cv-primary);
        }
        .edu-year {
            font-size: 10px;
            color: var(--cv-muted);
        }

        /* Certifications */
        .cert-item {
            font-size: 11px;
            margin-bottom: 6px;
        }
        .cert-name { font-weight: 600; }
        .cert-issuer { color: var(--cv-muted); }

        /* Social */
        .social-item {
            font-size: 10.5px;
            margin-bottom: 6px;
            color: var(--cv-sidebar-text);
        }
        .social-item a { color: #60a5fa; text-decoration: none; }

        /* Projects */
        .project-item {
            margin-bottom: 10px;
        }
        .project-name {
            font-size: 12px;
            font-weight: 600;
        }
        .project-desc {
            font-size: 10.5px;
            color: var(--cv-muted);
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Awards */
        .award-item {
            font-size: 11px;
            margin-bottom: 4px;
        }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    {{-- SIDEBAR --}}
    <aside class="sidebar">
        {{-- Photo --}}
        <div class="photo-section">
            <div class="photo-wrap">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
                @else
                    <div class="photo-fallback">{{ strtoupper(substr($candidate['full_name'] ?? 'U', 0, 1)) }}</div>
                @endif
            </div>
        </div>

        {{-- Contact --}}
        <div class="sidebar-section">
            <h3>Contact</h3>
            @if(!empty($candidate['phone']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                    {{ $candidate['phone'] }}
                </div>
            @endif
            @if(!empty($candidate['email']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    {{ $candidate['email'] }}
                </div>
            @endif
            @if(!empty($candidate['location']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $candidate['location'] }}
                </div>
            @endif
            @if(!empty($candidate['website']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                    <a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a>
                </div>
            @endif
            @if(!empty($candidate['linkedin']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-4 0v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    <a href="{{ $candidate['linkedin'] }}">{{ $candidate['linkedin'] }}</a>
                </div>
            @endif
            @if(!empty($candidate['address']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    {{ $candidate['address'] }}
                </div>
            @endif
            @if(!empty($candidate['date_of_birth']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ $candidate['date_of_birth'] }}
                </div>
            @endif
            @if(!empty($candidate['gender']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                    {{ $candidate['gender'] }}
                </div>
            @endif
            @if(!empty($candidate['nationality']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                    {{ $candidate['nationality'] }}
                </div>
            @endif
            @if(!empty($candidate['father_name']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Father: {{ $candidate['father_name'] }}
                </div>
            @endif
            @if(!empty($candidate['mother_name']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Mother: {{ $candidate['mother_name'] }}
                </div>
            @endif
            @if(!empty($candidate['religion']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    {{ $candidate['religion'] }}
                </div>
            @endif
            @if(!empty($candidate['blood_group']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>
                    Blood: {{ $candidate['blood_group'] }}
                </div>
            @endif
            @if(!empty($candidate['marital_status']))
                <div class="contact-item">
                    <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                    {{ $candidate['marital_status'] }}
                </div>
            @endif
        </div>

        {{-- Skills --}}
        @if(!empty($skills) && count($skills) > 0)
            <div class="sidebar-section">
                <h3>Key Skills</h3>
                @foreach($skills as $skill)
                    <span class="skill-tag">{{ is_array($skill) ? ($skill['name'] ?? $skill['label'] ?? '') : $skill }}</span>
                @endforeach
            </div>
        @endif

        {{-- Languages --}}
        @if(!empty($languages) && count($languages) > 0)
            <div class="sidebar-section">
                <h3>Languages</h3>
                @foreach($languages as $lang)
                    @php
                        $langName = is_array($lang) ? ($lang['name'] ?? $lang['language'] ?? '') : $lang;
                        $langLevel = is_array($lang) ? ($lang['proficiency'] ?? $lang['level'] ?? 'Intermediate') : 'Intermediate';
                        $dots = match($langLevel) {
                            'Native', 'Fluent', 'Advanced' => 5,
                            'Intermediate' => 3,
                            'Basic', 'Beginner' => 2,
                            default => 3,
                        };
                    @endphp
                    <div class="lang-item">
                        <span>{{ $langName }}</span>
                        <div class="lang-dots">
                            @for($i = 1; $i <= 5; $i++)
                                <div class="lang-dot {{ $i <= $dots ? 'filled' : '' }}"></div>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Social Links --}}
        @if(!empty($social_links) && count($social_links) > 0)
            <div class="sidebar-section">
                <h3>Online Profiles</h3>
                @foreach($social_links as $link)
                    @php
                        $linkUrl = is_array($link) ? ($link['url'] ?? $link['link'] ?? '') : $link;
                        $linkLabel = is_array($link) ? ($link['label'] ?? $link['platform'] ?? '') : '';
                    @endphp
                    @if($linkUrl)
                        <div class="social-item">
                            @if($linkLabel) <strong>{{ $linkLabel }}:</strong> @endif
                            <a href="{{ $linkUrl }}">{{ $linkUrl }}</a>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </aside>

    {{-- MAIN CONTENT --}}
    <div class="main-content">
        {{-- Header --}}
        <div class="cv-header">
            <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            @if(!empty($candidate['title']))
                <div class="title">{{ $candidate['title'] }}</div>
            @endif
        </div>

        {{-- Summary --}}
        @if(!empty($summary))
            <div class="main-section">
                <h2>Profile Summary</h2>
                <div class="summary-text">{{ $summary }}</div>
            </div>
        @endif

        {{-- Experience --}}
        @if(!empty($experience) && count($experience) > 0)
            <div class="main-section">
                <h2>Work Experience</h2>
                @foreach($experience as $exp)
                    <div class="exp-item">
                        <div class="exp-header">
                            <div class="exp-title">{{ $exp['position'] ?? $exp['title'] ?? '' }}</div>
                            <div class="exp-date">
                                {{ $exp['start_date'] ?? '' }}
                                @if(!empty($exp['end_date'])) – {{ $exp['end_date'] }} @else – Present @endif
                            </div>
                        </div>
                        <div class="exp-company">
                            {{ $exp['company'] ?? $exp['company_name'] ?? '' }}
                            @if(!empty($exp['location'])) · {{ $exp['location'] }} @endif
                        </div>
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

        {{-- Education --}}
        @if(!empty($education) && count($education) > 0)
            <div class="main-section">
                <h2>Education</h2>
                @foreach($education as $edu)
                    @php
                        $eduYear = $edu['start_date'] ?? $edu['year'] ?? $edu['duration'] ?? '';
                        $eduGpa = $edu['gpa_or_cgpa'] ?? $edu['gpa'] ?? $edu['cgpa'] ?? '';
                        $eduGrade = $edu['grade'] ?? $edu['result'] ?? '';
                        $eduField = $edu['field'] ?? $edu['group_or_subject'] ?? '';
                        $eduLocation = $edu['location'] ?? '';
                        $eduBoard = $edu['board'] ?? '';
                    @endphp
                    <div class="edu-item">
                        <div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div>
                        <div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div>
                        <div class="edu-year">
                            @if($eduField){{ $eduField }} @endif
                            @if($eduLocation) · {{ $eduLocation }} @endif
                            @if($eduBoard) · {{ $eduBoard }} @endif
                        </div>
                        <div class="edu-year">
                            @if($eduYear)
                                {{ $eduYear }}
                                @if(!empty($edu['end_date']) && $eduYear !== $edu['end_date']) – {{ $edu['end_date'] }} @endif
                            @endif
                            @if($eduGpa) · GPA: {{ $eduGpa }} @endif
                            @if($eduGrade) · {{ $eduGrade }} @elseif(!empty($edu['description'])) · {{ $edu['description'] }} @endif
                        </div>
                        @if(!empty($edu['description']))
                            <div class="edu-desc" style="font-size:10px;color:var(--cv-muted);margin-top:3px;line-height:1.5;word-wrap:break-word;">{{ $edu['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Certifications --}}
        @if(!empty($certifications) && count($certifications) > 0)
            <div class="main-section">
                <h2>Certifications</h2>
                @foreach($certifications as $cert)
                    @php
                        $certName = is_array($cert) ? ($cert['name'] ?? $cert['title'] ?? '') : $cert;
                        $certIssuer = is_array($cert) ? ($cert['issuer'] ?? $cert['organization'] ?? '') : '';
                        $certDate = is_array($cert) ? ($cert['date'] ?? '') : '';
                        if ($certDate) {
                            try { $certDate = \Carbon\Carbon::parse($certDate)->format('M Y'); } catch (\Exception $e) { $certDate = $certDate; }
                        }
                    @endphp
                    <div class="cert-item">
                        <span class="cert-name">{{ $certName }}</span>
                        @if($certIssuer) <span class="cert-issuer"> — {{ $certIssuer }}</span> @endif
                        @if($certDate) <span class="cert-issuer"> ({{ $certDate }})</span> @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Training --}}
        @if(!empty($training) && count($training) > 0)
            <div class="main-section">
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

        {{-- Projects --}}
        @if(!empty($projects) && count($projects) > 0)
            <div class="main-section">
                <h2>Projects</h2>
                @foreach($projects as $project)
                    <div class="project-item">
                        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;">
                            <div class="project-name">{{ $project['name'] ?? $project['title'] ?? '' }}</div>
                            @if(!empty($project['url']))
                                <a href="{{ $project['url'] }}" style="font-size:10px;color:var(--cv-primary);white-space:nowrap;text-decoration:none;">{{ $project['url'] }}</a>
                            @endif
                        </div>
                        @if(!empty($project['technologies']))
                            <div style="font-size:10px;color:var(--cv-primary);margin-bottom:3px;">{{ is_array($project['technologies']) ? implode(', ', $project['technologies']) : $project['technologies'] }}</div>
                        @endif
                        @if(!empty($project['description']))
                            <div class="project-desc">{{ $project['description'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Awards --}}
        @if(!empty($awards) && count($awards) > 0)
            <div class="main-section">
                <h2>Awards</h2>
                @foreach($awards as $award)
                    @php
                        $awardName = is_array($award) ? ($award['name'] ?? $award['title'] ?? '') : $award;
                        $awardIssuer = is_array($award) ? ($award['issuer'] ?? '') : '';
                        $awardDate = is_array($award) ? ($award['date'] ?? '') : '';
                    @endphp
                    <div class="award-item">
                        {{ $awardName }}
                        @if($awardIssuer) <span style="color:var(--cv-muted);"> — {{ $awardIssuer }}</span> @endif
                        @if($awardDate) <span style="color:var(--cv-muted);"> ({{ $awardDate }})</span> @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Hobbies --}}
        @if(!empty($hobbies) && count($hobbies) > 0)
            <div class="main-section">
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

        {{-- References --}}
        @if(!empty($references) && count($references) > 0)
            <div class="main-section">
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

        {{-- Signature Section --}}
        <div class="cv-signature-section" style="margin-top: 24px; padding-top: 8px; display: flex; justify-content: flex-end; page-break-inside: avoid !important; break-inside: avoid !important; width: 100%;">
            <div style="text-align: center; min-width: 190px; display: inline-block;">
                @if(!empty($personal['signature_url']))
                    <div style="height: 40px; margin-bottom: 4px; display: flex; align-items: flex-end; justify-content: center;">
                        <img src="{{ $personal['signature_url'] }}" alt="Signature" style="max-height: 38px; max-width: 150px; object-fit: contain;" />
                    </div>
                @else
                    <div style="height: 35px;"></div>
                @endif
                <div style="border-top: 1.5px solid #334155; width: 180px; margin: 0 auto 5px auto;"></div>
                <div style="font-size: 13px; font-weight: 700; color: var(--cv-text, #1e293b); letter-spacing: 0.3px;">{{ $candidate['full_name'] ?? $personal['full_name'] ?? 'Authorized Signature' }}</div>
                <div style="font-size: 10.5px; color: var(--cv-muted, #64748b); margin-top: 2px;">স্বাক্ষর ও তারিখ / Signature & Date</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

