<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Charcoal Ribbon</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #1a1a1a; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; height: auto; page-break-after: auto; } }

        .top-header { display: grid; grid-template-columns: 210px 1fr; background: #262626; color: #fff; }
        .header-photo-box { background: #9ca3af; padding: 18px; display: flex; align-items: center; justify-content: center; position: relative; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); }
        .avatar-circle { width: 130px; height: 130px; border-radius: 50%; overflow: hidden; background: #111; border: 3px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.4); }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 46px; font-weight: 800; color: #fff; }

        .header-text-box { padding: 28px 32px; display: flex; flex-direction: column; justify-content: center; }
        .header-text-box h1 { font-family: 'Montserrat', sans-serif; font-size: 30px; font-weight: 900; letter-spacing: 1px; color: #fff; text-transform: uppercase; margin-bottom: 2px; }
        .header-text-box .job-title { font-size: 13px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .header-text-box .bio-text { font-size: 10px; line-height: 1.5; color: #d1d5db; }

        .main-body { display: grid; grid-template-columns: 210px 1fr; flex: 1; min-height: 100%; }

        .left-panel { background: #18181b; color: #fff; padding: 24px 18px; display: flex; flex-direction: column; gap: 20px; }
        .left-sec-title { font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; text-align: center; color: #fff; border-bottom: 1px solid #3f3f46; padding-bottom: 4px; margin-bottom: 10px; }

        .contact-row { display: flex; align-items: center; gap: 10px; font-size: 9.5px; color: #e4e4e7; margin-bottom: 8px; word-break: break-all; }
        .contact-ico { width: 22px; height: 22px; border-radius: 50%; border: 1px solid #71717a; display: flex; align-items: center; justify-content: center; font-size: 10px; flex-shrink: 0; }

        .skill-item { margin-bottom: 8px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; text-align: center; }
        .skill-dots { display: flex; justify-content: center; gap: 3px; margin-top: 2px; }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #52525b; }
        .dot.filled { background: #fff; }

        .lang-grid { display: flex; justify-content: space-around; text-align: center; margin-top: 4px; }
        .lang-circle { width: 44px; height: 44px; border-radius: 50%; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; margin: 0 auto 3px; background: rgba(255,255,255,0.1); }
        .lang-name { font-size: 9px; color: #d4d4d8; font-weight: 600; }

        .passions-row { display: flex; justify-content: space-around; font-size: 16px; margin-top: 4px; }

        .right-panel { background: #fff; color: #111; padding: 24px 28px; display: flex; flex-direction: column; gap: 18px; }

        .ribbon-heading { background: #262626; color: #fff; font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; padding: 6px 16px; display: inline-flex; align-items: center; justify-content: space-between; position: relative; clip-path: polygon(0 0, 95% 0, 100% 50%, 95% 100%, 0 100%); margin-bottom: 12px; min-width: 170px; }

        .content-card { margin-bottom: 14px; page-break-inside: avoid; break-inside: avoid; }
        .card-year { font-family: 'Montserrat', sans-serif; font-size: 11.5px; font-weight: 800; color: #000; margin-bottom: 1px; }
        .card-sub { font-size: 10.5px; font-weight: 700; color: #4b5563; margin-bottom: 3px; }
        .card-text { font-size: 9.5px; line-height: 1.55; color: #4b5563; }

        .footer-strip { text-align: right; font-size: 8.5px; color: #9ca3af; font-weight: 700; letter-spacing: 1px; margin-top: auto; padding-top: 12px; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="top-header">
        <div class="header-photo-box">
            <div class="avatar-circle">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'F', 0, 1)) }}</div>
                @endif
            </div>
        </div>
        <div class="header-text-box">
            <h1>{{ $candidate['full_name'] ?? 'FRANS DENISON' }}</h1>
            <div class="job-title">{{ $candidate['title'] ?? '' }}</div>
            @if(!empty($summary))
            <div class="bio-text">{{ $summary }}</div>
            @endif
        </div>
    </div>

    <div class="main-body">
        <div class="left-panel">
            <div>
                <div class="left-sec-title">CONTACT</div>
                @if(!empty($candidate['phone']))
                <div class="contact-row"><div class="contact-ico">📞</div><span>{{ $candidate['phone'] }}</span></div>
                @endif
                @if(!empty($candidate['email']))
                <div class="contact-row"><div class="contact-ico">✉️</div><span>{{ $candidate['email'] }}</span></div>
                @endif
                @if(!empty($candidate['location']))
                <div class="contact-row"><div class="contact-ico">📍</div><span>{{ $candidate['location'] }}</span></div>
                @endif
                @if(!empty($candidate['website']))
                <div class="contact-row"><div class="contact-ico">🌐</div><span>{{ $candidate['website'] }}</span></div>
                @endif
            </div>

            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="left-sec-title">SKILLS</div>
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                @if($sn)
                <div class="skill-item">
                    <div>{{ $sn }}</div>
                    <div class="skill-dots">
                        <div class="dot filled"></div>
                        <div class="dot filled"></div>
                        <div class="dot filled"></div>
                        <div class="dot filled"></div>
                        <div class="dot filled"></div>
                        <div class="dot filled"></div>
                        <div class="dot filled"></div>
                        <div class="dot"></div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div>
                <div class="left-sec-title">LANGUAGE</div>
                <div class="lang-grid">
                    @foreach($languages as $l)
                    @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; @endphp
                    <div>
                        <div class="lang-circle">100%</div>
                        <div class="lang-name">{{ $ln }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($hobbies) && count($hobbies) > 0)
            <div>
                <div class="left-sec-title">PASSIONS</div>
                <div class="passions-row">
                    @php $passIcons = ['📷', '✏️', '🎵', '📖', '✈️', '🎮']; @endphp
                    @foreach($hobbies as $idx => $h)
                    @if($idx < 4)<span>{{ $passIcons[$idx % count($passIcons)] }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="right-panel">
            @if(!empty($summary))
            <div class="content-card">
                <div class="ribbon-heading">ABOUT ME 👤</div>
                <div class="card-text">{{ $summary }}</div>
            </div>
            @endif

            @if(!empty($education) && count($education) > 0)
            <div class="content-card">
                <div class="ribbon-heading">EDUCATION 🎓</div>
                @foreach($education as $ed)
                <div style="margin-bottom: 10px;">
                    <div class="card-year">{{ $ed['start_date'] ?? '' }}{{ !empty($ed['end_date']) ? ' - ' . $ed['end_date'] : '' }} · {{ $ed['degree'] ?? '' }}</div>
                    <div class="card-sub">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                    @if(!empty($ed['description']))<div class="card-text">{{ $ed['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div class="content-card">
                <div class="ribbon-heading">EXPERIENCE 💼</div>
                @foreach($experience as $e)
                <div style="margin-bottom: 10px;">
                    <div class="card-year">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' - ' . $e['end_date'] : ' - Present' }} · {{ $e['position'] ?? '' }}</div>
                    <div class="card-sub">{{ $e['company'] ?? '' }}@if(!empty($e['location'])) · {{ $e['location'] }}@endif</div>
                    @if(!empty($e['description']))<div class="card-text">{{ $e['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            <div class="footer-strip">{{ $candidate['website'] ?? 'WWW.EJOBS.BD' }}</div>
        </div>
    </div>
</div>
</body>
</html>