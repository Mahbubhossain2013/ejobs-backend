<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Executive Navy</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;0,600;0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #152232; color: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #152232; padding: 28px 32px; display: flex; flex-direction: column; gap: 20px; position: relative; overflow: visible;     align-items: stretch;
        }
        @media print { body { background: #152232; } .cv-page { width: 100%; min-height: auto; } }

        /* Top Grid */
        .top-row { display: grid; grid-template-columns: 1fr 1.2fr; gap: 20px; align-items: start; }
        
        .avatar-wrap { width: 130px; height: 140px; border-radius: 12px; overflow: hidden; background: #1f3148; margin-bottom: 12px; }
        .avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 800; color: #f5a623; }

        .name-big { font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 900; line-height: 1.1; margin-bottom: 6px; }
        .name-gold { color: #f5a623; }
        .title-badge { background: #f5a623; color: #000; font-family: 'Montserrat', sans-serif; font-size: 11px; font-weight: 800; padding: 4px 14px; border-radius: 20px; display: inline-block; }

        /* Top Right Card */
        .profile-card { background: #f5a623; color: #000; border-radius: 20px; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
        .profile-card-title { font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 900; font-style: italic; text-transform: uppercase; display: flex; align-items: center; gap: 6px; color: #000; }
        .profile-card-desc { font-size: 10px; line-height: 1.5; color: #1c1917; font-weight: 500; }
        
        .card-contact { display: flex; flex-direction: column; gap: 6px; font-size: 10px; font-weight: 700; color: #000; }
        .card-contact-item { display: flex; align-items: center; gap: 8px; }
        .card-contact-icon { width: 20px; height: 20px; border-radius: 50%; background: #000; color: #f5a623; display: flex; align-items: center; justify-content: center; font-size: 10px; }

        /* Bottom 2-Column Body */
        .body-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; flex: 1; }

        .sec-head { display: flex; align-items: center; gap: 8px; font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 900; font-style: italic; color: #f5a623; text-transform: uppercase; border-bottom: 2px solid #f5a623; padding-bottom: 4px; margin-bottom: 12px; }
        .sec-head-icon { width: 22px; height: 22px; background: #f5a623; color: #000; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; }

        .entry-box { margin-bottom: 14px; }
        .entry-title { font-family: 'Montserrat', sans-serif; font-size: 11.5px; font-weight: 800; color: #f5a623; margin-bottom: 2px; }
        .entry-sub { font-size: 10.5px; color: #94a3b8; font-weight: 600; margin-bottom: 2px; }
        .entry-desc { font-size: 9.5px; line-height: 1.5; color: #cbd5e1; }

        .bullet-list { list-style: none; padding: 0; }
        .bullet-list li { font-size: 10.5px; color: #e2e8f0; margin-bottom: 6px; position: relative; padding-left: 14px; }
        .bullet-list li::before { content: "-"; position: absolute; left: 0; color: #f5a623; font-weight: 700; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="top-row">
        <div>
            <div class="avatar-wrap">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'Y', 0, 1)) }}</div>
                @endif
            </div>
            <div class="name-big"><span class="name-gold">{{ $candidate['first_name'] ?: 'Your' }}</span> {{ $candidate['last_name'] ?: 'Name' }}</div>
            <div class="title-badge">{{ $candidate['title'] ?? '' }}</div>
        </div>

        <div class="profile-card">
            @if(!empty($summary))
            <div>
                <div class="profile-card-title">👤 PROFILE</div>
                <div class="profile-card-desc">{{ $summary }}</div>
            </div>
            @endif

            <div class="card-contact">
                @if(!empty($candidate['phone']))
                <div class="card-contact-item"><div class="card-contact-icon">📞</div><span>{{ $candidate['phone'] }}</span></div>
                @endif
                @if(!empty($candidate['email']))
                <div class="card-contact-item"><div class="card-contact-icon">✉️</div><span>{{ $candidate['email'] }}</span></div>
                @endif
                @if(!empty($candidate['website']))
                <div class="card-contact-item"><div class="card-contact-icon">🌐</div><span>{{ $candidate['website'] }}</span></div>
                @endif
                @if(!empty($candidate['location']))
                <div class="card-contact-item"><div class="card-contact-icon">📍</div><span>{{ $candidate['location'] }}</span></div>
                @endif
            </div>
        </div>
    </div>

    <div class="body-grid">
        <!-- Left Sub-column -->
        <div>
            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-head"><div class="sec-head-icon">💡</div> EXPERIENCE</div>
                @foreach($experience as $e)
                <div class="entry-box">
                    <div class="entry-title">{{ $e['position'] ?? $e['title'] ?? '' }} {{ !empty($e['start_date']) ? '(' . $e['start_date'] . (!empty($e['end_date']) ? ' - ' . $e['end_date'] : ' - Present') . ')' : '' }}</div>
                    @if(!empty($e['company']))<div class="entry-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>@endif
                    @if(!empty($e['description']))<div class="entry-desc">{{ $e['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div style="margin-top: 16px;">
                <div class="sec-head"><div class="sec-head-icon">✏️</div> LANGUAGES</div>
                <ul class="bullet-list">
                    @foreach($languages as $l)
                    @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                    <li><strong>{{ $ln }}</strong> @if($lp)<span>({{ $lp }})</span>@endif</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <!-- Right Sub-column -->
        <div>
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-head"><div class="sec-head-icon">🎓</div> EDUCATION</div>
                @foreach($education as $ed)
                <div class="entry-box">
                    <div class="entry-title">{{ $ed['institution'] ?? $ed['school'] ?? '' }} {{ !empty($ed['start_date']) ? '(' . $ed['start_date'] . (!empty($ed['end_date']) ? ' - ' . $ed['end_date'] : '') . ')' : '' }}</div>
                    <div class="entry-sub">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                    @if(!empty($ed['description']))<div class="entry-desc">{{ $ed['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top: 16px;">
                <div class="sec-head"><div class="sec-head-icon">⚡</div> SKILLS</div>
                <ul class="bullet-list">
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                    @if($sn)<li>{{ $sn }}</li>@endif
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>