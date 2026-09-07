<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Three Band</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,500;0,600;0,700;0,800;0,900;1,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fff; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }

        /* Top Band (Dark Navy) */
        .band-top { background: #232b38; color: #fff; padding: 28px 32px; display: grid; grid-template-columns: 140px 1fr; gap: 24px; align-items: center; }
        .avatar-circle { width: 120px; height: 120px; border-radius: 50%; border: 4px solid #fff; overflow: hidden; background: #1a202c; box-shadow: 0 4px 14px rgba(0,0,0,0.5); }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 800; color: #e6a15c; }

        .band-top-content h1 { font-family: 'Montserrat', sans-serif; font-size: 26px; font-weight: 800; color: #e6a15c; margin-bottom: 2px; }
        .band-top-content .prof { font-size: 13px; font-weight: 600; color: #fff; margin-bottom: 8px; }
        .band-top-content .desc { font-size: 10px; line-height: 1.5; color: #cbd5e1; }

        .contact-row-top { display: flex; flex-wrap: wrap; gap: 10px 18px; margin-top: 10px; font-size: 10px; color: #e2e8f0; }
        .contact-row-top span { display: flex; align-items: center; gap: 4px; }

        /* Middle Band (White) */
        .band-middle { background: #fff; color: #000; padding: 28px 32px; display: grid; grid-template-columns: 120px 1fr; gap: 24px; align-items: start; }
        .badge-circle { width: 100px; height: 100px; border-radius: 50%; border: 2px solid #000; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto; background: #fafafa; }
        
        .band-title { font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 800; color: #000; margin-bottom: 12px; }
        .item-row { margin-bottom: 12px; }
        .item-main { font-family: 'Montserrat', sans-serif; font-size: 12px; font-weight: 800; color: #000; }
        .item-sub { font-size: 10.5px; font-weight: 600; color: #64748b; margin-bottom: 2px; }
        .item-desc { font-size: 9.5px; line-height: 1.5; color: #475569; }

        /* Bottom Band (Golden Caramel) */
        .band-bottom { background: #e6a15c; color: #000; padding: 28px 32px; display: grid; grid-template-columns: 1fr 120px; gap: 24px; align-items: start; flex: 1; }
        .band-bottom .band-title { color: #000; }
        .band-bottom .item-main { color: #000; }
        .band-bottom .item-sub { color: #1c1917; }
        .band-bottom .item-desc { color: #1c1917; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <!-- Top Band -->
    <div class="band-top">
        <div style="text-align:center;">
            <div class="avatar-circle">
                @if(!empty($candidate['photo_url']))
                    <img src="{{ $candidate['photo_url'] }}" alt="{{ $candidate['full_name'] ?? '' }}">
                @else
                    <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'N', 0, 1)) }}</div>
                @endif
            </div>
        </div>
        <div class="band-top-content">
            <h1>{{ $candidate['full_name'] ?? 'Name Surname' }}</h1>
            <div class="prof">{{ $candidate['title'] ?? '' }}</div>
            @if(!empty($summary))
            <div class="desc">{{ $summary }}</div>
            @endif
            <div class="contact-row-top">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span>@endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span>@endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
                @if(!empty($candidate['website']))<span>🌐 {{ $candidate['website'] }}</span>@endif
            </div>
        </div>
    </div>

    <!-- Middle Band (Education & Skills) -->
    <div class="band-middle">
        <div class="badge-circle">🎓</div>
        <div>
            <div class="band-title">Education</div>
            @if(!empty($education) && count($education) > 0)
                @foreach($education as $ed)
                <div class="item-row">
                    <div class="item-main">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                    <div class="item-sub">{{ $ed['institution'] ?? $ed['school'] ?? '' }} {{ !empty($ed['start_date']) ? '(' . $ed['start_date'] . (!empty($ed['end_date']) ? ' - ' . $ed['end_date'] : '') . ')' : '' }}</div>
                    @if(!empty($ed['description']))<div class="item-desc">{{ $ed['description'] }}</div>@endif
                </div>
                @endforeach
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top: 10px;">
                <strong style="font-size:11px;text-transform:uppercase;">Skills: </strong>
                @foreach($skills as $s)
                @php $sn = is_array($s) ? ($s['name'] ?? $s['skill'] ?? '') : $s; @endphp
                <span style="font-size:10px;background:#f1f5f9;padding:2px 6px;border-radius:4px;margin-right:4px;">{{ $sn }}</span>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Bottom Band (Experience) -->
    <div class="band-bottom">
        <div>
            <div class="band-title">Experience</div>
            @if(!empty($experience) && count($experience) > 0)
                @foreach($experience as $e)
                <div class="item-row">
                    <div class="item-main">{{ $e['position'] ?? $e['title'] ?? '' }}</div>
                    <div class="item-sub">{{ $e['company'] ?? $e['company_name'] ?? '' }}@if(!empty($e['location'])), {{ $e['location'] }}@endif {{ !empty($e['start_date']) ? '(' . $e['start_date'] . (!empty($e['end_date']) ? ' - ' . $e['end_date'] : ' - Present') . ')' : '' }}</div>
                    @if(!empty($e['description']))<div class="item-desc">{{ $e['description'] }}</div>@endif
                </div>
                @endforeach
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div style="margin-top: 10px;">
                <strong style="font-size:11px;text-transform:uppercase;">Languages: </strong>
                @foreach($languages as $l)
                @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; @endphp
                <span style="font-size:10px;font-weight:700;margin-right:8px;">{{ $ln }}</span>
                @endforeach
            </div>
            @endif
        </div>
        <div class="badge-circle" style="background:#fef3c7;">💼</div>
    </div>
</div>
</body>
</html>