<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Editorial Taupe</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;0,600;0,700;0,800;0,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #eae5df; color: #1c1917; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column; position: relative;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }

        /* Top Header Graphic Wave */
        .top-wave { display: grid; grid-template-columns: 200px 1fr; background: #fff; position: relative; }
        .top-left-curve { background: #1c2533; border-bottom-right-radius: 90px; padding: 24px 20px; display: flex; align-items: center; justify-content: center; }
        .avatar-circle { width: 130px; height: 130px; border-radius: 50%; border: 4px solid #fff; overflow: hidden; background: #2a3442; }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 46px; font-weight: 800; color: #fff; }

        .top-right-wave { background: #b8977e; border-top-left-radius: 90px; padding: 32px 36px 20px; color: #fff; display: flex; flex-direction: column; justify-content: center; }
        .top-right-wave h1 { font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 900; line-height: 1.1; color: #fff; margin-bottom: 8px; }

        .title-strip { background: #ded2c6; color: #6b4e3d; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; padding: 6px 36px; }
        .feedback-sub { padding: 8px 36px; font-size: 10.5px; color: #78716c; font-style: italic; border-bottom: 1px solid #f5f5f4; }

        /* Body 3-Column: Left Timeline, Center Vertical Ribbons, Right Content */
        .body-layout { display: grid; grid-template-columns: 210px 42px 1fr; padding: 18px 24px; gap: 16px; flex: 1; }

        /* Left Timeline */
        .left-timeline { border-left: 2px solid #d6c7b8; padding-left: 14px; display: flex; flex-direction: column; gap: 18px; margin-left: 8px; }
        .timeline-sec-title { font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 2px; color: #b8977e; text-transform: uppercase; margin-bottom: 6px; position: relative; }
        .timeline-sec-title::before { content: ''; position: absolute; left: -21px; top: 3px; width: 8px; height: 8px; border-radius: 50%; background: #b8977e; }

        .timeline-item { font-size: 10px; font-weight: 600; color: #1c1917; margin-bottom: 4px; }
        .timeline-skill { font-size: 9.5px; font-weight: 800; color: #1c1917; text-transform: uppercase; margin-bottom: 3px; }

        /* Center Vertical Badges */
        .center-ribbons { display: flex; flex-direction: column; gap: 18px; }
        .ribbon-box { width: 36px; border-radius: 4px; writing-mode: vertical-rl; transform: rotate(180deg); display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; font-size: 10px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; padding: 12px 6px; text-align: center; }
        .ribbon-profile { background: #b8977e; color: #fff; height: 110px; }
        .ribbon-edu { background: #ded2c6; color: #6b4e3d; height: 140px; }
        .ribbon-exp { background: #1c2533; color: #fff; height: 160px; }

        /* Right Content Sections */
        .right-content { display: flex; flex-direction: column; gap: 18px; }
        .content-block { padding-bottom: 12px; border-bottom: 1px solid #e7e5e4; }
        .content-block:last-child { border-bottom: none; }

        .content-title { font-family: 'Montserrat', sans-serif; font-size: 11.5px; font-weight: 800; color: #1c1917; text-transform: uppercase; margin-bottom: 2px; }
        .content-sub { font-size: 10px; color: #b8977e; font-weight: 700; margin-bottom: 3px; }
        .content-desc { font-size: 9.5px; line-height: 1.5; color: #57534e; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="top-wave">
        <div class="top-left-curve">
            <div class="avatar-circle">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'N', 0, 1)) }}</div>
                @endif
            </div>
        </div>
        <div class="top-right-wave">
            <h1>{{ $candidate['full_name'] ?? 'Name Surname' }}</h1>
        </div>
    </div>

    <div class="title-strip">{{ $candidate['title'] ?? '' }}</div>
    <div class="feedback-sub">Hello, I will be glad to receive your feedback</div>

    <div class="body-layout">
        <!-- Left Timeline -->
        <div class="left-timeline">
            <div>
                <div class="timeline-sec-title">CONTACT</div>
                @if(!empty($candidate['phone']))<div class="timeline-item">{{ $candidate['phone'] }}</div>@endif
                @if(!empty($candidate['email']))<div class="timeline-item">{{ $candidate['email'] }}</div>@endif
                @if(!empty($candidate['location']))<div class="timeline-item">{{ $candidate['location'] }}</div>@endif
                @if(!empty($candidate['website']))<div class="timeline-item">{{ $candidate['website'] }}</div>@endif
            </div>

            @if(!empty($languages) && count($languages) > 0)
            <div>
                <div class="timeline-sec-title">LANGUAGE</div>
                @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; @endphp
                <div class="timeline-skill">{{ $ln }}</div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="timeline-sec-title">SKILLS</div>
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                <div class="timeline-skill">{{ $sn }}</div>
                @endforeach
            </div>
            @endif

            @if(!empty($hobbies) && count($hobbies) > 0)
            <div>
                <div class="timeline-sec-title">HOBBY</div>
                @foreach($hobbies as $h)
                @php $hn = is_array($h) ? ($h['name'] ?? $h['hobby'] ?? '') : $h; @endphp
                <div class="timeline-skill">{{ $hn }}</div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Center Ribbons -->
        <div class="center-ribbons">
            <div class="ribbon-box ribbon-profile">PROFILE</div>
            <div class="ribbon-box ribbon-edu">EDUCATION</div>
            <div class="ribbon-box ribbon-exp">EXPERIENCE</div>
        </div>

        <!-- Right Content -->
        <div class="right-content">
            <div class="content-block" style="min-height: 110px;">
                @if(!empty($summary))
                <div class="content-desc">{{ $summary }}</div>
                
                @endif
            </div>

            <div class="content-block" style="min-height: 130px;">
                @if(!empty($education) && count($education) > 0)
                    @foreach($education as $ed)
                    <div style="margin-bottom: 8px;">
                        <div class="content-title">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                        <div class="content-sub">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }} {{ !empty($ed['start_date']) ? '(' . $ed['start_date'] . (!empty($ed['end_date']) ? ' - ' . $ed['end_date'] : '') . ')' : '' }}</div>
                        @if(!empty($ed['description']))<div class="content-desc">{{ $ed['description'] }}</div>@endif
                    </div>
                    @endforeach
                @endif
            </div>

            <div class="content-block" style="min-height: 150px;">
                @if(!empty($experience) && count($experience) > 0)
                    @foreach($experience as $e)
                    <div style="margin-bottom: 8px;">
                        <div class="content-title">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
                        <div class="content-sub">{{ $e['position'] ?? $e['title'] ?? '' }} {{ !empty($e['start_date']) ? '(' . $e['start_date'] . (!empty($e['end_date']) ? ' - ' . $e['end_date'] : ' - Present') . ')' : '' }}</div>
                        @if(!empty($e['description']))<div class="content-desc">{{ $e['description'] }}</div>@endif
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>