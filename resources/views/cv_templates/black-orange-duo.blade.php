<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Orange Duo</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,600;0,700;0,800;0,900;1,700;1,800;1,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #000; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #000; display: flex; align-items: stretch; }
        @page {
            size: A4 portrait;
            margin: 0 !important;
        }
        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #000 !important;
                width: 210mm !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .cv-page {
                width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .item-box, .contact-item, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item, .interest-card {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Left Column (Black) */
        .left-col { width: 44%; background: #000; color: #fff; padding: 32px 24px; display: flex; flex-direction: column; gap: 18px; }
        .name-title-left { font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 900; color: #f59e0b; line-height: 1.1; margin-bottom: 4px; }
        .subtitle-left { font-size: 15px; font-weight: 500; color: #fff; margin-bottom: 14px; }

        .photo-frame { width: 145px; height: 145px; margin: 0 auto 10px; border: 3px solid #f59e0b; padding: 3px; background: #000; }
        .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
        .photo-init { width: 100%; height: 100%; background: #1c1917; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 800; color: #f59e0b; font-family: 'Montserrat', sans-serif; }

        .sec-orange { font-family: 'Montserrat', sans-serif; font-size: 15px; font-weight: 900; font-style: italic; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #f59e0b; padding-bottom: 4px; margin-bottom: 10px; display: block; }
        .left-desc { font-size: 12px; line-height: 1.6; color: #d4d4d8; }

        .bullet-list-left { list-style: none; padding: 0; }
        .bullet-list-left li { font-size: 12.5px; color: #e4e4e7; margin-bottom: 5px; position: relative; padding-left: 14px; }
        .bullet-list-left li::before { content: "-"; position: absolute; left: 0; color: #f59e0b; font-weight: 700; }

        .contact-item { display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: #e4e4e7; margin-bottom: 9px; word-break: break-all; }
        .contact-icon { width: 24px; height: 24px; border-radius: 50%; background: #fff; color: #000; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }

        /* Right Column (Warm Golden Amber) */
        .right-col { width: 56%; background: #f59e0b; color: #000; padding: 32px 28px; display: flex; flex-direction: column; gap: 18px; }
        .sec-black { font-family: 'Montserrat', sans-serif; font-size: 16px; font-weight: 900; font-style: italic; color: #000; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 10px; display: block; }

        .item-box { margin-bottom: 12px; }
        .item-title { font-family: 'Montserrat', sans-serif; font-size: 14px; font-weight: 800; color: #000; margin-bottom: 2px; }
        .item-sub { font-size: 13px; font-weight: 700; color: #1c1917; margin-bottom: 2px; }
        .item-desc { font-size: 12px; line-height: 1.5; color: #1c1917; }

        .bullet-list-right { list-style: none; padding: 0; }
        .bullet-list-right li { font-size: 13px; color: #000; font-weight: 700; margin-bottom: 4px; position: relative; padding-left: 14px; }
        .bullet-list-right li::before { content: "-"; position: absolute; left: 0; color: #000; font-weight: 900; }

        .interests-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; margin-top: 6px; }
        .interest-card { background: rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.15); border-radius: 6px; padding: 8px 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; }
        .interest-icon { font-size: 18px; }
        .interest-name { font-size: 10.5px; font-weight: 800; color: #000; text-transform: uppercase; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="left-col">
        <div>
            <h1 class="name-title-left">{{ $candidate['full_name'] ?? 'Your Name' }}</h1>
            <div class="subtitle-left">{{ $candidate['title'] ?? '' }}</div>
        </div>

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
            <div class="sec-orange">PROFESSIONAL GOALS</div>
            <div class="left-desc">{{ $summary }}</div>
        </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="sec-orange">SPECIALIZATIONS</div>
            <ul class="bullet-list-left">
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                @if($sn)<li>{{ $sn }}</li>@endif
                @endforeach
            </ul>
        </div>
        @endif

        <div>
            <div class="sec-orange">GET IN TOUCH</div>
            <div>
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
            <div class="sec-orange">PERSONAL DETAILS</div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                @if(!empty($candidate['father_name']))
                <div class="contact-item"><div class="contact-icon">👨</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Father's Name</div><span>{{ $candidate['father_name'] }}</span></div></div>
                @endif
                @if(!empty($candidate['mother_name']))
                <div class="contact-item"><div class="contact-icon">👩</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Mother's Name</div><span>{{ $candidate['mother_name'] }}</span></div></div>
                @endif
                @if(!empty($candidate['date_of_birth']) || !empty($candidate['dob']))
                <div class="contact-item"><div class="contact-icon">📅</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Date of Birth</div><span>{{ $candidate['date_of_birth'] ?: $candidate['dob'] }}</span></div></div>
                @endif
                @if(!empty($candidate['gender']))
                <div class="contact-item"><div class="contact-icon">👤</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Gender</div><span>{{ $candidate['gender'] }}</span></div></div>
                @endif
                @if(!empty($candidate['marital_status']))
                <div class="contact-item"><div class="contact-icon">💍</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Marital Status</div><span>{{ $candidate['marital_status'] }}</span></div></div>
                @endif
                @if(!empty($candidate['religion']))
                <div class="contact-item"><div class="contact-icon">🕊️</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Religion</div><span>{{ $candidate['religion'] }}</span></div></div>
                @endif
                @if(!empty($candidate['blood_group']))
                <div class="contact-item"><div class="contact-icon">🩸</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Blood Group</div><span>{{ $candidate['blood_group'] }}</span></div></div>
                @endif
                @if(!empty($candidate['nationality']))
                <div class="contact-item"><div class="contact-icon">🌐</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Nationality</div><span>{{ $candidate['nationality'] }}</span></div></div>
                @endif
                @if(!empty($candidate['nid']))
                <div class="contact-item"><div class="contact-icon">🪪</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">NID / Passport</div><span>{{ $candidate['nid'] }}</span></div></div>
                @endif
                @if(!empty($candidate['permanent_address']))
                <div class="contact-item"><div class="contact-icon">🏠</div><div><div style="font-size:10.5px;color:#cbd5e1;text-transform:uppercase;">Permanent Address</div><span>{{ $candidate['permanent_address'] }}</span></div></div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="right-col">
        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-black">WORK EXPERIENCE</div>
            @foreach($experience as $e)
            <div class="item-box">
                <div class="item-title">{{ $e['position'] ?? $e['title'] ?? '' }} {{ !empty($e['start_date']) ? '(' . $e['start_date'] . (!empty($e['end_date']) ? ' - ' . $e['end_date'] : ' - Present') . ')' : '' }}</div>
                @if(!empty($e['company']))<div class="item-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>@endif
                @if(!empty($e['description']))<div class="item-desc">{{ $e['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-black">ACADEMIC HISTORY</div>
            @foreach($education as $ed)
            <div class="item-box">
                <div class="item-title">{{ $ed['institution'] ?? $ed['school'] ?? '' }} {{ !empty($ed['start_date']) ? '(' . $ed['start_date'] . (!empty($ed['end_date']) ? ' - ' . $ed['end_date'] : '') . ')' : '' }}</div>
                <div class="item-sub">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                @if(!empty($ed['description']))<div class="item-desc">{{ $ed['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
        <div>
            <div class="sec-black">LANGUAGES</div>
            <ul class="bullet-list-right">
                @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                <li>{{ $ln }} @if($lp)<span>({{ $lp }})</span>@endif</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($hobbies) && count($hobbies) > 0)
        <div>
            <div class="sec-black">INTERESTS</div>
            <div class="interests-grid">
                @php $hobbyIcons = ['📖', '🕺', '🎧', '🏋️', '✈️', '🎮', '🎬', '🎨', '🎵', '💻']; @endphp
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
    </div>
</div>
</body>
</html>