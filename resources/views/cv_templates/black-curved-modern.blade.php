<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Modern Black</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,600;0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #121212; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #000; display: flex; position: relative; overflow: visible;     align-items: stretch;
        }
        @media print { body { background: #000; } .cv-page { width: 100%; min-height: auto; } }

        .cv-page::before {
            content: '';
            position: absolute;
            top: -40px;
            right: 0;
            width: 58%;
            height: 180px;
            background: #52525b;
            border-bottom-left-radius: 120px;
            z-index: 1;
        }

        .left-col { width: 44%; background: #000; color: #fff; padding: 32px 24px; position: relative; z-index: 2; display: flex; flex-direction: column; gap: 20px; border-right: 1px solid #27272a; }
        .photo-frame { width: 140px; height: 140px; margin: 0 auto 6px; border: 4px solid #52525b; padding: 4px; background: #000; box-shadow: 0 10px 25px rgba(0,0,0,0.8); }
        .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
        .photo-init { width: 100%; height: 100%; background: #27272a; display: flex; align-items: center; justify-content: center; font-size: 46px; font-weight: 800; color: #fff; font-family: 'Montserrat', sans-serif; }

        .pill-heading { background: #52525b; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 900; font-style: italic; text-transform: uppercase; letter-spacing: 1px; padding: 6px 16px; border-radius: 2px; display: inline-block; margin-bottom: 10px; }

        .about-text { font-size: 10.5px; line-height: 1.6; color: #d4d4d8; }
        .bullet-list { list-style: none; padding: 0; }
        .bullet-list li { font-size: 11px; color: #e4e4e7; margin-bottom: 6px; position: relative; padding-left: 14px; }
        .bullet-list li::before { content: "-"; position: absolute; left: 0; color: #a1a1aa; font-weight: 700; }

        .contact-item { display: flex; align-items: center; gap: 10px; font-size: 10.5px; color: #e4e4e7; margin-bottom: 10px; word-break: break-all; }
        .contact-icon { width: 26px; height: 26px; border-radius: 50%; background: #27272a; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; color: #fff; }

        .right-col { width: 56%; background: #fff; color: #000; padding: 32px 28px; position: relative; z-index: 2; display: flex; flex-direction: column; gap: 18px; }

        .header-box { margin-bottom: 10px; position: relative; z-index: 3; }
        .name-title { font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 900; font-style: italic; color: #fff; text-transform: uppercase; letter-spacing: 1px; line-height: 1.1; margin-bottom: 4px; text-shadow: 0 2px 8px rgba(0,0,0,0.5); }
        .subtitle { font-size: 13px; font-weight: 600; color: #f4f4f5; text-shadow: 0 1px 4px rgba(0,0,0,0.5); }

        .sec-bar { background: #fff; color: #000; font-family: 'Montserrat', sans-serif; font-size: 14px; font-weight: 900; font-style: italic; text-transform: uppercase; letter-spacing: 1px; border-bottom: 3px solid #000; padding-bottom: 4px; margin-bottom: 10px; display: block; }

        .item-box { margin-bottom: 12px; }
        .item-title { font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; color: #000; margin-bottom: 2px; }
        .item-sub { font-size: 11px; font-weight: 700; color: #52525b; margin-bottom: 3px; }
        .item-desc { font-size: 10px; line-height: 1.5; color: #3f3f46; }

        .interests-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center; margin-top: 6px; }
        .interest-card { background: #f4f4f5; border: 1px solid #e4e4e7; border-radius: 6px; padding: 10px 6px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; }
        .interest-icon { font-size: 20px; }
        .interest-name { font-size: 9px; font-weight: 700; color: #27272a; text-transform: uppercase; }

        .ref-box { margin-bottom: 6px; }
        .ref-name { font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; color: #000; }
        .ref-sub { font-size: 10px; color: #52525b; font-weight: 600; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="left-col">
        <div class="photo-frame">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="photo-init" style="display:none;">{{ strtoupper(substr($candidate['full_name'] ?? 'Y', 0, 1)) }}</div>
            @else
                <div class="photo-init">{{ strtoupper(substr($candidate['full_name'] ?? 'Y', 0, 1)) }}</div>
            @endif
        </div>

        @if(!empty($summary))
        <div>
            <div class="pill-heading">ABOUT ME</div>
            <div class="about-text">{{ $summary }}</div>
        </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="pill-heading">SKILLS</div>
            <ul class="bullet-list">
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                @if($sn)<li>{{ $sn }}</li>@endif
                @endforeach
            </ul>
        </div>
        @endif

        <div>
            <div class="pill-heading">CONTACT</div>
            <div style="margin-top: 4px;">
                @if(!empty($candidate['phone']))
                <div class="contact-item"><div class="contact-icon">📞</div><span>{{ $candidate['phone'] }}</span></div>
                @endif
                @if(!empty($candidate['alt_phone']))
                <div class="contact-item"><div class="contact-icon">📱</div><span>{{ $candidate['alt_phone'] }}</span></div>
                @endif
                @if(!empty($candidate['email']))
                <div class="contact-item"><div class="contact-icon">✉️</div><span>{{ $candidate['email'] }}</span></div>
                @endif
                @if(!empty($candidate['website']))
                <div class="contact-item"><div class="contact-icon">🌐</div><span>{{ $candidate['website'] }}</span></div>
                @endif
                @if(!empty($candidate['location']))
                <div class="contact-item"><div class="contact-icon">📍</div><span>{{ $candidate['location'] }}</span></div>
                @endif
            </div>
        </div>

        @php
            $hasPersonal = !empty($candidate['father_name']) || !empty($candidate['mother_name']) || 
                           !empty($candidate['date_of_birth']) || !empty($candidate['dob']) || 
                           !empty($candidate['gender']) || !empty($candidate['marital_status']) || 
                           !empty($candidate['religion']) || !empty($candidate['blood_group']) || 
                           !empty($candidate['nid']) || !empty($candidate['nationality']) || 
                           !empty($candidate['permanent_address']);
        @endphp
        @if($hasPersonal)
        <div>
            <div class="pill-heading">PERSONAL INFO</div>
            <div style="margin-top: 4px; display: flex; flex-direction: column; gap: 8px;">
                @if(!empty($candidate['father_name']))
                <div class="contact-item"><div class="contact-icon">👨</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Father's Name</div><span>{{ $candidate['father_name'] }}</span></div></div>
                @endif
                @if(!empty($candidate['mother_name']))
                <div class="contact-item"><div class="contact-icon">👩</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Mother's Name</div><span>{{ $candidate['mother_name'] }}</span></div></div>
                @endif
                @if(!empty($candidate['date_of_birth']) || !empty($candidate['dob']))
                <div class="contact-item"><div class="contact-icon">📅</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Date of Birth</div><span>{{ $candidate['date_of_birth'] ?: $candidate['dob'] }}</span></div></div>
                @endif
                @if(!empty($candidate['gender']))
                <div class="contact-item"><div class="contact-icon">👤</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Gender</div><span>{{ $candidate['gender'] }}</span></div></div>
                @endif
                @if(!empty($candidate['marital_status']))
                <div class="contact-item"><div class="contact-icon">💍</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Marital Status</div><span>{{ $candidate['marital_status'] }}</span></div></div>
                @endif
                @if(!empty($candidate['religion']))
                <div class="contact-item"><div class="contact-icon">🕊️</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Religion</div><span>{{ $candidate['religion'] }}</span></div></div>
                @endif
                @if(!empty($candidate['blood_group']))
                <div class="contact-item"><div class="contact-icon">🩸</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Blood Group</div><span>{{ $candidate['blood_group'] }}</span></div></div>
                @endif
                @if(!empty($candidate['nationality']))
                <div class="contact-item"><div class="contact-icon">🌐</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Nationality</div><span>{{ $candidate['nationality'] }}</span></div></div>
                @endif
                @if(!empty($candidate['nid']))
                <div class="contact-item"><div class="contact-icon">🪪</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">NID / Passport</div><span>{{ $candidate['nid'] }}</span></div></div>
                @endif
                @if(!empty($candidate['permanent_address']))
                <div class="contact-item"><div class="contact-icon">🏠</div><div><div style="font-size:8.5px;color:#a1a1aa;text-transform:uppercase;">Permanent Address</div><span>{{ $candidate['permanent_address'] }}</span></div></div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="right-col">
        <div class="header-box">
            <h1 class="name-title">{{ $candidate['full_name'] ?? 'YOUR NAME' }}</h1>
            <div class="subtitle">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-bar">EDUCATION</div>
            @foreach($education as $ed)
            <div class="item-box">
                <div class="item-title">{{ $ed['institution'] ?? $ed['school'] ?? '' }} {{ !empty($ed['start_date']) ? '(' . $ed['start_date'] . (!empty($ed['end_date']) ? ' - ' . $ed['end_date'] : '') . ')' : '' }}</div>
                <div class="item-sub">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                @if(!empty($ed['description']))<div class="item-desc">{{ $ed['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-bar">WORK EXPERIENCE</div>
            @foreach($experience as $e)
            <div class="item-box">
                <div class="item-title">{{ $e['position'] ?? $e['title'] ?? '' }} {{ !empty($e['start_date']) ? '(' . $e['start_date'] . (!empty($e['end_date']) ? ' - ' . $e['end_date'] : ' - Present') . ')' : '' }}</div>
                <div class="item-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>
                @if(!empty($e['description']))<div class="item-desc">{{ $e['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($references) && count($references) > 0)
        <div>
            <div class="sec-bar">REFERENCES</div>
            @foreach($references as $r)
            <div class="ref-box">
                <div class="ref-name">{{ $r['name'] ?? '' }}</div>
                <div class="ref-sub">{{ $r['designation'] ?? '' }}@if(!empty($r['organization'])) - {{ $r['organization'] }}@endif @if(!empty($r['phone'])) · {{ $r['phone'] }}@endif</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($hobbies) && count($hobbies) > 0)
        <div>
            <div class="sec-bar">INTERESTS</div>
            <div class="interests-grid">
                @php $hobbyIcons = ['🎬', '💻', '🎙️', '🏋️', '📝', '🎵', '✈️', '🎮', '📚', '🎨']; @endphp
                @foreach($hobbies as $idx => $h)
                @php $hn = is_array($h) ? ($h['name'] ?? $h['hobby'] ?? '') : $h; @endphp
                @if($hn)
                <div class="interest-card">
                    <div class="interest-icon">{{ $hobbyIcons[$idx % count($hobbyIcons)] }}</div>
                    <div class="interest-name">{{ $hn }}</div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
        <div>
            <div class="sec-bar">LANGUAGES</div>
            <ul class="bullet-list" style="color:#000;">
                @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                <li style="color:#18181b;"><strong>{{ $ln }}</strong> @if($lp)<span style="color:#71717a;">({{ $lp }})</span>@endif</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($projects) && count($projects) > 0)
        <div>
            <div class="sec-bar">KEY PROJECTS</div>
            @foreach($projects as $p)
            <div class="item-box">
                <div class="item-title">{{ $p['name'] ?? $p['title'] ?? '' }}</div>
                @if(!empty($p['url']))<div class="item-sub" style="color:#2563eb;">{{ $p['url'] }}</div>@endif
                @if(!empty($p['description']))<div class="item-desc">{{ $p['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($certifications) && count($certifications) > 0)
        <div>
            <div class="sec-bar">CERTIFICATIONS</div>
            @foreach($certifications as $c)
            <div class="item-box">
                <div class="item-title">{{ $c['name'] ?? $c['title'] ?? '' }} {{ !empty($c['date']) ? '(' . $c['date'] . ')' : '' }}</div>
                @if(!empty($c['issuer']))<div class="item-sub">{{ $c['issuer'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($training) && count($training) > 0)
        <div>
            <div class="sec-bar">TRAINING & WORKSHOPS</div>
            @foreach($training as $tr)
            <div class="item-box">
                <div class="item-title">{{ $tr['title'] ?? $tr['name'] ?? '' }} {{ !empty($tr['duration']) ? '(' . $tr['duration'] . ')' : '' }}</div>
                @if(!empty($tr['institute']))<div class="item-sub">{{ $tr['institute'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
</body>
</html>