<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Cyan Ocean</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #002b49; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; height: auto; page-break-after: auto; } }

        /* Top Ocean Banner */
        .top-banner { background: #003b64; color: #fff; padding: 24px 32px; display: grid; grid-template-columns: 1fr 140px; gap: 20px; align-items: center; position: relative; }
        .top-banner::after { content: ''; position: absolute; bottom: -20px; left: 0; width: 100%; height: 30px; background: #00a8e8; clip-path: ellipse(65% 100% at 50% 0%); z-index: 1; }

        .name-main { font-family: 'Montserrat', sans-serif; font-size: 30px; font-weight: 900; letter-spacing: 1px; color: #fff; text-transform: uppercase; }
        .name-title-cyan { font-size: 13px; font-weight: 700; color: #00a8e8; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 2px; }

        .avatar-wrap { width: 130px; height: 130px; border-radius: 50%; border: 4px solid #fff; overflow: hidden; background: #002b49; box-shadow: 0 4px 15px rgba(0,0,0,0.5); position: relative; z-index: 2; }
        .avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 46px; font-weight: 800; color: #00a8e8; }

        /* Body Split */
        .body-split { display: grid; grid-template-columns: 210px 1fr; flex: 1; min-height: 100%; align-items: stretch; }

        /* Left Column (Ocean Blue) */
        .left-col { background: #003b64; color: #fff; padding: 36px 18px 24px; display: flex; flex-direction: column; gap: 20px; min-height: 100%; align-self: stretch; }
        .sec-cyan-title { font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 1.5px; color: #fff; text-transform: uppercase; display: flex; align-items: center; gap: 6px; border-bottom: 2px solid #00a8e8; padding-bottom: 4px; margin-bottom: 10px; }

        .contact-item { display: flex; align-items: center; gap: 8px; font-size: 9.5px; color: #e0f2fe; margin-bottom: 8px; word-break: break-all; }
        .contact-ico { width: 22px; height: 22px; border-radius: 50%; background: #00a8e8; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; flex-shrink: 0; }

        .skill-line { display: flex; justify-content: space-between; align-items: center; font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #e0f2fe; margin-bottom: 6px; }
        .stars { color: #00a8e8; }

        .lang-pills { display: flex; justify-content: space-around; text-align: center; }
        .lang-pct { width: 42px; height: 42px; border-radius: 50%; background: #fff; color: #003b64; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; margin: 0 auto 3px; }
        .lang-lbl { font-size: 9px; color: #e0f2fe; font-weight: 700; }

        /* Right Column (White) */
        .right-col { background: #fff; color: #111; padding: 36px 28px 24px; display: flex; flex-direction: column; gap: 18px; }
        .pill-bar-head { background: #00a8e8; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; padding: 6px 16px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px; }

        .card-box { margin-bottom: 12px; page-break-inside: avoid; break-inside: avoid; }
        .card-title-main { font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; color: #000; text-transform: uppercase; display: flex; justify-content: space-between; }
        .card-title-sub { font-size: 10.5px; font-weight: 700; color: #00a8e8; text-transform: uppercase; margin-bottom: 3px; }
        .card-desc { font-size: 9.5px; line-height: 1.55; color: #475569; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="top-banner">
        <div>
            <h1 class="name-main">{{ $candidate['full_name'] ?? 'JOHN SMITH' }}</h1>
            <div class="name-title-cyan">{{ $candidate['title'] ?? '' }}</div>
        </div>
        <div>
            <div class="avatar-wrap">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="avatar-init" style="display:none;">{{ strtoupper(substr($candidate['full_name'] ?? 'J', 0, 1)) }}</div>
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'J', 0, 1)) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="body-split">
        <!-- Left Panel -->
        <div class="left-col">
            <div>
                <div class="sec-cyan-title">💬 CONTACT</div>
                @if(!empty($candidate['phone']))
                <div class="contact-item"><div class="contact-ico">📞</div><span>{{ $candidate['phone'] }}</span></div>
                @endif
                @if(!empty($candidate['alt_phone']))
                <div class="contact-item"><div class="contact-ico">📱</div><span>{{ $candidate['alt_phone'] }}</span></div>
                @endif
                @if(!empty($candidate['email']))
                <div class="contact-item"><div class="contact-ico">✉️</div><span>{{ $candidate['email'] }}</span></div>
                @endif
                @if(!empty($candidate['location']))
                <div class="contact-item"><div class="contact-ico">📍</div><span>{{ $candidate['location'] }}</span></div>
                @endif
                @if(!empty($candidate['website']))
                <div class="contact-item"><div class="contact-ico">🌐</div><span>{{ $candidate['website'] }}</span></div>
                @endif
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
                <div class="sec-cyan-title">👤 PERSONAL DETAILS</div>
                <div style="display: flex; flex-direction: column; gap: 6px; font-size: 11px;">
                    @if(!empty($candidate['father_name']))
                    <div class="contact-item"><div class="contact-ico">👨</div><span>Father: {{ $candidate['father_name'] }}</span></div>
                    @endif
                    @if(!empty($candidate['mother_name']))
                    <div class="contact-item"><div class="contact-ico">👩</div><span>Mother: {{ $candidate['mother_name'] }}</span></div>
                    @endif
                    @if(!empty($candidate['date_of_birth']) || !empty($candidate['dob']))
                    <div class="contact-item"><div class="contact-ico">📅</div><span>DOB: {{ $candidate['date_of_birth'] ?: $candidate['dob'] }}</span></div>
                    @endif
                    @if(!empty($candidate['gender']))
                    <div class="contact-item"><div class="contact-ico">⚧</div><span>Gender: {{ $candidate['gender'] }}</span></div>
                    @endif
                    @if(!empty($candidate['marital_status']))
                    <div class="contact-item"><div class="contact-ico">💍</div><span>Status: {{ $candidate['marital_status'] }}</span></div>
                    @endif
                    @if(!empty($candidate['religion']))
                    <div class="contact-item"><div class="contact-ico">🕊️</div><span>Religion: {{ $candidate['religion'] }}</span></div>
                    @endif
                    @if(!empty($candidate['blood_group']))
                    <div class="contact-item"><div class="contact-ico">🩸</div><span>Blood: {{ $candidate['blood_group'] }}</span></div>
                    @endif
                    @if(!empty($candidate['nationality']))
                    <div class="contact-item"><div class="contact-ico">🌐</div><span>Nationality: {{ $candidate['nationality'] }}</span></div>
                    @endif
                    @if(!empty($candidate['nid']))
                    <div class="contact-item"><div class="contact-ico">🪪</div><span>NID: {{ $candidate['nid'] }}</span></div>
                    @endif
                    @if(!empty($candidate['permanent_address']))
                    <div class="contact-item"><div class="contact-ico">🏠</div><span>Permanent: {{ $candidate['permanent_address'] }}</span></div>
                    @endif
                </div>
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="sec-cyan-title">💡 SKILLS</div>
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                @if($sn)
                <div class="skill-line"><span>{{ $sn }}</span><span class="stars">★★★</span></div>
                @endif
                @endforeach
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div>
                <div class="sec-cyan-title">🌐 LANGUAGE</div>
                <div class="lang-pills">
                    @foreach($languages as $l)
                    @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; @endphp
                    <div>
                        <div class="lang-pct">100%</div>
                        <div class="lang-lbl">{{ $ln }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right Panel -->
        <div class="right-col">
            @if(!empty($summary))
            <div class="card-box">
                <div class="pill-bar-head">👤 ABOUT ME</div>
                <div class="card-desc">{{ $summary }}</div>
            </div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div class="card-box">
                <div class="pill-bar-head">💼 EXPERIENCE</div>
                @foreach($experience as $e)
                <div style="margin-bottom: 10px;">
                    <div class="card-title-main"><span>{{ $e['company'] ?? '' }}</span> <span style="font-size:9.5px;color:#64748b;">@if(!empty($e['start_date'])){{ $e['start_date'] }}@if(!empty($e['end_date'])) - {{ $e['end_date'] }}@else - Present @endif @endif</span></div>
                    <div class="card-title-sub">{{ $e['position'] ?? '' }}</div>
                    @if(!empty($e['description']))<div class="card-desc">{{ $e['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($education) && count($education) > 0)
            <div class="card-box">
                <div class="pill-bar-head">🎓 EDUCATION</div>
                @foreach($education as $ed)
                <div style="margin-bottom: 10px;">
                    <div class="card-title-main"><span>{{ $ed['institution'] ?? $ed['school'] ?? '' }}</span> <span style="font-size:9.5px;color:#64748b;">@if(!empty($ed['start_date'])){{ $ed['start_date'] }}@if(!empty($ed['end_date'])) - {{ $ed['end_date'] }}@endif @endif</span></div>
                    <div class="card-title-sub">{{ $ed['degree'] ?? '' }}</div>
                    @if(!empty($ed['description']))<div class="card-desc">{{ $ed['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>