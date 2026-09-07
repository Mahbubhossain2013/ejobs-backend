<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Academic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cv-primary: #2d3748;
            --cv-accent: #8b4513;
            --cv-text: #2d3748;
            --cv-bg: #ffffff;
            --cv-muted: #718096;
            --cv-light: #f7f3ee;
            --cv-border: #e8e0d6;
            --cv-gold: #c9a96e;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Lato', sans-serif;
            color: var(--cv-text);
            background: #f5f2ed;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body { overflow: visible !important; }
        .cv-page {
            width: 210mm;
            min-height: auto;
            margin: 0 auto;
            background: var(--cv-bg);
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px 36px;
        }
        @media print {
            body { background: white; }
            .cv-page { box-shadow: none; margin: 0; width: 100%; min-height: auto; }
        }
        /* Header */
        .academic-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--cv-gold);
            margin-bottom: 20px;
        }
        .academic-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--cv-primary);
            letter-spacing: 0.5px;
        }
        .academic-header .title {
            font-size: 13px;
            color: var(--cv-accent);
            font-weight: 400;
            font-style: italic;
            margin-top: 4px;
        }
        .academic-contact {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px 16px;
            margin-top: 10px;
            font-size: 10px;
            color: var(--cv-muted);
        }
        .academic-contact span { white-space: nowrap; }
        .academic-contact .sep { color: var(--cv-gold); }
        .academic-contact a { color: var(--cv-accent); text-decoration: none; }
        .personal-details { display: flex; justify-content: center; flex-wrap: wrap; gap: 4px 16px; margin-top: 6px; font-size: 9.5px; color: var(--cv-muted); }

        /* Photo */
        .photo-wrap {
            width: 90px;
            height: 90px;
            margin: 0 auto 12px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--cv-gold);
        }
        .photo-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .photo-fallback {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            background: var(--cv-light);
            color: var(--cv-accent);
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
        }

        /* Sections */
        .section {
            margin-bottom: 18px;
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
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
            background: linear-gradient(to right, var(--cv-gold), transparent);
        }

        /* Summary */
        .summary-text {
            font-size: 11px;
            line-height: 1.7;
            color: var(--cv-muted);
            font-style: italic;
            padding: 0 4px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Experience — publication-style */
        .pub-item {
            margin-bottom: 12px;
            padding-left: 12px;
            border-left: 2px solid var(--cv-light);
        }
        .pub-item:hover { border-left-color: var(--cv-gold); }
        .pub-title {
            font-family: 'Playfair Display', serif;
            font-size: 12px;
            font-weight: 600;
            font-style: italic;
            color: var(--cv-primary);
        }
        .pub-authors {
            font-size: 10.5px;
            color: var(--cv-muted);
            margin: 2px 0 1px;
        }
        .pub-venue {
            font-size: 10px;
            color: var(--cv-accent);
        }
        .pub-date {
            font-size: 9.5px;
            color: var(--cv-muted);
        }
        .pub-desc {
            font-size: 10px;
            line-height: 1.5;
            color: var(--cv-muted);
            margin-top: 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .pub-desc ul { padding-left: 14px; }
        .pub-desc li { margin-bottom: 1px; word-wrap: break-word; }

        /* Education */
        .edu-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .edu-card {
            background: var(--cv-light);
            padding: 10px 12px;
            border-radius: 4px;
        }
        .edu-degree {
            font-family: 'Playfair Display', serif;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--cv-primary);
        }
        .edu-school {
            font-size: 10.5px;
            color: var(--cv-accent);
            margin: 2px 0;
        }
        .edu-year {
            font-size: 9.5px;
            color: var(--cv-muted);
        }
        .edu-grade {
            font-size: 9.5px;
            color: var(--cv-muted);
        }

        /* Skills */
        .skills-cluster {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .skill-group {
            background: var(--cv-light);
            border-radius: 4px;
            padding: 6px 10px;
            flex: 1;
            min-width: 120px;
        }
        .skill-group-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--cv-accent);
            margin-bottom: 3px;
        }
        .skill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
        }
        .skill-list span {
            font-size: 9.5px;
            color: var(--cv-muted);
        }
        .skill-list span:not(:last-child)::after {
            content: ', ';
        }

        /* Projects */
        .project-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--cv-border);
        }
        .project-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .project-name {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--cv-primary);
        }
        .project-desc {
            font-size: 10px;
            color: var(--cv-muted);
            line-height: 1.5;
            margin-top: 2px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Awards */
        .award-item {
            font-size: 10.5px;
            margin-bottom: 4px;
            padding-left: 10px;
            position: relative;
        }
        .award-item::before {
            content: '\2605';
            position: absolute;
            left: 0;
            color: var(--cv-gold);
            font-size: 9px;
        }

        /* Certifications */
        .cert-item {
            font-size: 10.5px;
            margin-bottom: 4px;
        }
        .cert-name { font-weight: 600; }
        .cert-issuer { color: var(--cv-muted); }

        /* Languages */
        .lang-line {
            display: inline-block;
            margin-right: 14px;
            font-size: 10px;
        }
        .lang-name { font-weight: 600; }
        .lang-level { color: var(--cv-muted); }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    {{-- HEADER --}}
    <div class="academic-header">
        @if(!empty($candidate['photo_url']) || !empty($candidate['full_name']))
            @if(!empty($candidate['photo_url']))
                <div class="photo-wrap"><img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}"></div>
            @endif
        @endif
        <h1>{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
        @if(!empty($candidate['title']))
            <div class="title">{{ $candidate['title'] }}</div>
        @endif
        <div class="academic-contact">
            @if(!empty($candidate['email']))<span>{{ $candidate['email'] }}</span>@endif
            @if(!empty($candidate['phone']))<span class="sep">|</span><span>{{ $candidate['phone'] }}</span>@endif
            @if(!empty($candidate['location']))<span class="sep">|</span><span>{{ $candidate['location'] }}</span>@endif
            @if(!empty($candidate['website']))<span class="sep">|</span><span><a href="{{ $candidate['website'] }}">{{ $candidate['website'] }}</a></span>@endif
        </div>
        @if(!empty($social_links) && count($social_links) > 0)
            <div style="margin-top:10px;">
                @foreach($social_links as $link)
                    @php $linkUrl = is_array($link) ? ($link['url'] ?? $link['link'] ?? '') : $link; $linkLabel = is_array($link) ? ($link['label'] ?? $link['platform'] ?? '') : ''; @endphp
                    @if($linkUrl)<div style="font-size:10px;color:var(--cv-muted);margin-bottom:3px;">@if($linkLabel)<span style="color:var(--cv-accent);font-weight:600;">{{ $linkLabel }}:</span> @endif<a href="{{ $linkUrl }}" style="color:var(--cv-accent);text-decoration:none;">{{ $linkUrl }}</a></div>@endif
                @endforeach
            </div>
        @endif
        <div class="personal-details">
            @if(!empty($candidate['date_of_birth']))<span>{{ $candidate['date_of_birth'] }}</span>@endif
            @if(!empty($candidate['gender']))<span>{{ $candidate['gender'] }}</span>@endif
            @if(!empty($candidate['nationality']))<span>{{ $candidate['nationality'] }}</span>@endif
            @if(!empty($candidate['father_name']))<span>Father: {{ $candidate['father_name'] }}</span>@endif
            @if(!empty($candidate['mother_name']))<span>Mother: {{ $candidate['mother_name'] }}</span>@endif
            @if(!empty($candidate['religion']))<span>{{ $candidate['religion'] }}</span>@endif
            @if(!empty($candidate['blood_group']))<span>Blood: {{ $candidate['blood_group'] }}</span>@endif
            @if(!empty($candidate['marital_status']))<span>{{ $candidate['marital_status'] }}</span>@endif
        </div>
    </div>

    {{-- SUMMARY --}}
    @if(!empty($summary))
        <div class="section">
            <div class="section-header"><h2>Summary</h2><div class="rule"></div></div>
            <div class="summary-text">{{ $summary }}</div>
        </div>
    @endif

    {{-- EXPERIENCE (publication-style) --}}
    @if(!empty($experience) && count($experience) > 0)
        <div class="section">
            <div class="section-header"><h2>Experience</h2><div class="rule"></div></div>
            @foreach($experience as $exp)
                @php
                    $pos = $exp['position'] ?? $exp['title'] ?? '';
                    $comp = $exp['company'] ?? $exp['company_name'] ?? '';
                    $loc = $exp['location'] ?? '';
                @endphp
                <div class="pub-item">
                    <div class="pub-title">{{ $pos }}</div>
                    <div class="pub-authors">{{ $comp }}@if($loc), {{ $loc }}@endif</div>
                    <div class="pub-venue">{{ $exp['start_date'] ?? '' }}{{ !empty($exp['end_date']) ? ' — ' . $exp['end_date'] : ' — Present' }}</div>
                    @if(!empty($exp['description']))
                        <div class="pub-desc">
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

    {{-- EDUCATION --}}
    @if(!empty($education) && count($education) > 0)
        <div class="section">
            <div class="section-header"><h2>Education</h2><div class="rule"></div></div>
            <div class="edu-row">
                @foreach($education as $edu)
                    <div class="edu-card">
                        <div class="edu-degree">{{ $edu['degree'] ?? $edu['qualification'] ?? '' }}</div>
                        <div class="edu-school">{{ $edu['institution'] ?? $edu['school'] ?? '' }}</div>
                        <div class="edu-year">{{ $edu['start_date'] ?? '' }}@if(!empty($edu['end_date'])) — {{ $edu['end_date'] }}@endif</div>
                        @if(!empty($edu['grade']))<div class="edu-grade">GPA: {{ $edu['grade'] }}</div>@elseif(!empty($edu['description']))<div class="edu-grade">{{ $edu['description'] }}</div>@endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- SKILLS --}}
    @if(!empty($skills) && count($skills) > 0)
        <div class="section">
            <div class="section-header"><h2>Skills</h2><div class="rule"></div></div>
            <div class="skills-cluster">
                @php
                    $grouped = [];
                    foreach($skills as $skill) {
                        $name = is_array($skill) ? ($skill['name'] ?? $skill['label'] ?? '') : $skill;
                        $category = is_array($skill) ? ($skill['category'] ?? $skill['type'] ?? 'General') : 'General';
                        $grouped[$category][] = $name;
                    }
                @endphp
                @foreach($grouped as $cat => $items)
                    <div class="skill-group">
                        <div class="skill-group-label">{{ $cat }}</div>
                        <div class="skill-list">@foreach($items as $s)<span>{{ $s }}</span>@endforeach</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- PROJECTS --}}
    @if(!empty($projects) && count($projects) > 0)
        <div class="section">
            <div class="section-header"><h2>Research & Projects</h2><div class="rule"></div></div>
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

    {{-- CERTIFICATIONS --}}
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
                <div class="cert-item">@if($ci)<span class="cert-name">{{ $ci }}</span> — {{ $cn }}@else<span class="cert-name">{{ $cn }}</span>@endif@if($certDate) <span style="font-size:9px;color:var(--cv-muted);">({{ $certDate }})</span>@endif</div>
            @endforeach
        </div>
    @endif

    {{-- TRAINING --}}
    @if(!empty($training) && count($training) > 0)
        <div class="section">
            <div class="section-header"><h2>Training</h2><div class="rule"></div></div>
            @foreach($training as $t)
                <div class="cert-item">
                    <span class="cert-name">{{ $t['title'] ?? '' }}</span>
                    @if(!empty($t['institute'])) — {{ $t['institute'] }} @endif
                    @if(!empty($t['duration'])) <span style="font-size:9px;color:var(--cv-muted);">({{ $t['duration'] }})</span> @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- LANGUAGES --}}
    @if(!empty($languages) && count($languages) > 0)
        <div class="section">
            <div class="section-header"><h2>Languages</h2><div class="rule"></div></div>
            <div>
                @foreach($languages as $lang)
                    @php
                        $ln = is_array($lang) ? ($lang['name'] ?? $lang['language'] ?? '') : $lang;
                        $ll = is_array($lang) ? ($lang['proficiency'] ?? $lang['level'] ?? '') : '';
                    @endphp
                    <span class="lang-line"><span class="lang-name">{{ $ln }}</span>@if($ll)<span class="lang-level"> ({{ $ll }})</span>@endif</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- AWARDS --}}
    @if(!empty($awards) && count($awards) > 0)
        <div class="section">
            <div class="section-header"><h2>Awards & Honors</h2><div class="rule"></div></div>
            @foreach($awards as $award)
                <div class="award-item">{{ is_array($award) ? ($award['name'] ?? $award['title'] ?? '') : $award }}</div>
            @endforeach
        </div>
    @endif

    {{-- REFERENCES --}}
    @if(!empty($references) && count($references) > 0)
        <div class="section">
            <div class="section-header"><h2>References</h2><div class="rule"></div></div>
            @foreach($references as $ref)
                <div class="cert-item">
                    <span class="cert-name">{{ $ref['name'] ?? '' }}</span>
                    @if(!empty($ref['designation'])) — {{ $ref['designation'] }} @endif
                    @if(!empty($ref['organization']))<br/>{{ $ref['organization'] }} @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- HOBBIES --}}
    @if(!empty($hobbies) && count($hobbies) > 0)
        <div class="section">
            <div class="section-header"><h2>Hobbies & Interests</h2><div class="rule"></div></div>
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
</body>
</html>
