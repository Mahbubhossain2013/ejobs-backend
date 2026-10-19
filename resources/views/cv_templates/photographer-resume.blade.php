<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Photographer</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --peach: #f97316; --peach-bg: #ffedd5; --text: #1c1917; --muted: #78716c; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #ffedd5; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .main { flex: 1; padding: 28px 24px; display: flex; flex-direction: column; gap: 16px; }
        .side { width: 34%; background: var(--peach-bg); padding: 28px 18px; display: flex; flex-direction: column; gap: 16px; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #c2410c; border-bottom: 2px solid #fdba74; padding-bottom: 3px; margin-bottom: 8px; }
        .slider-bar { width: 100%; height: 6px; background: #fed7aa; border-radius: 3px; overflow: hidden; margin-top: 3px; }
        .slider-fill { height: 100%; background: var(--peach); border-radius: 3px; }
        .exp-item { margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #1c1917; }
        .exp-co { font-size: 9.5px; color: var(--peach); font-weight: 600; margin-bottom: 2px; }
        .exp-desc { font-size: 9px; color: #57534e; line-height: 1.5; }
        .exp-desc ul { padding-left: 14px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="main">
        <div style="display:flex;align-items:center;gap:16px;border-bottom:2px solid #fed7aa;padding-bottom:14px;">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" style="width:68px;height:68px;border-radius:12px;object-fit:cover;" alt="">
            @endif
            <div>
                <h1 style="font-size:22px;font-weight:800;">{{ $candidate['full_name'] ?? 'Jonathan Stapler' }}</h1>
                <div style="font-size:10.5px;color:var(--peach);font-weight:700;">{{ $candidate['title'] ?? '' }}</div>
            </div>
        </div>

        @if(!empty($summary))
        <div><div class="sec-title">Profile</div><p style="font-size:9.5px;line-height:1.6;color:#44403c;">{{ $summary }}</p></div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">Experience</div>
            @foreach($experience as $e)
            <div class="exp-item">
                <div class="exp-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                <div class="exp-co">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
                @if(!empty($e['description']))
                <div class="exp-desc">
                    @if(preg_match('/<[^>]+>/', $e['description'])) {!! $e['description'] !!} @else
                    @php $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $e['description']))); @endphp
                    @if(count($lines) > 1)<ul>@foreach($lines as $l)<li>{{ $l }}</li>@endforeach</ul>@else<p>{{ $e['description'] }}</p>@endif
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @if(!empty($projects) && count($projects) > 0)
        <div>
            <div class="sec-title">Key Projects</div>
            @foreach($projects as $p)
            <div class="exp-item">
                <div class="exp-head"><div>{{ $p['name'] ?? '' }}</div>@if(!empty($p['url']))<a href="{{ $p['url'] }}" target="_blank" style="font-size:8.5px;color:var(--peach);">Link</a>@endif</div>
                @if(!empty($p['description']))<div class="exp-desc">{{ $p['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($training) && count($training) > 0)
        <div>
            <div class="sec-title">Training & Courses</div>
            @foreach($training as $t)
            <div class="exp-item">
                <div class="exp-head"><div>{{ $t['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $t['duration'] ?? '' }}</div></div>
                <div class="exp-co">{{ $t['institute'] ?? $t['institution'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($references) && count($references) > 0)
        <div>
            <div class="sec-title">References</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                @foreach($references as $r)
                <div style="font-size:9px;line-height:1.4;background:#fff7ed;padding:6px;border-radius:4px;">
                    <strong>{{ $r['name'] ?? '' }}</strong>
                    @if(!empty($r['designation']))<div>{{ $r['designation'] }}{{ !empty($r['organization']) ? ', ' . $r['organization'] : '' }}</div>@endif
                    @if(!empty($r['phone']))<div>📞 {{ $r['phone'] }}</div>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="side">
        <div>
            <div class="sec-title">Contact</div>
            @if(!empty($candidate['phone']))<div style="font-size:9px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div style="font-size:9px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="font-size:9px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
        </div>

        @php
            $photoPersonalInfo = [];
            if(!empty($candidate['father_name'])) $photoPersonalInfo[] = ['label' => "Father's Name", 'val' => $candidate['father_name']];
            if(!empty($candidate['mother_name'])) $photoPersonalInfo[] = ['label' => "Mother's Name", 'val' => $candidate['mother_name']];
            if(!empty($candidate['dob'])) $photoPersonalInfo[] = ['label' => 'Date of Birth', 'val' => $candidate['dob']];
            if(!empty($candidate['gender'])) $photoPersonalInfo[] = ['label' => 'Gender', 'val' => ucfirst($candidate['gender'])];
            if(!empty($candidate['marital_status'])) $photoPersonalInfo[] = ['label' => 'Marital Status', 'val' => ucfirst($candidate['marital_status'])];
            if(!empty($candidate['nationality'])) $photoPersonalInfo[] = ['label' => 'Nationality', 'val' => $candidate['nationality']];
            if(!empty($candidate['religion'])) $photoPersonalInfo[] = ['label' => 'Religion', 'val' => $candidate['religion']];
            if(!empty($candidate['blood_group'])) $photoPersonalInfo[] = ['label' => 'Blood Group', 'val' => $candidate['blood_group']];
            if(!empty($candidate['nid'])) $photoPersonalInfo[] = ['label' => 'NID', 'val' => $candidate['nid']];
        @endphp
        @if(count($photoPersonalInfo) > 0)
        <div>
            <div class="sec-title">Personal Details</div>
            @foreach($photoPersonalInfo as $pi)
            <div style="font-size:8.5px;margin-bottom:3px;color:#44403c;"><span style="color:#78716c;font-weight:600;">{{ $pi['label'] }}:</span> {{ $pi['val'] }}</div>
            @endforeach
        </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div class="sec-title">Expertise</div>
            @foreach($skills as $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
            @if($sn)
            <div style="margin-bottom:6px;">
                <div style="font-size:9px;font-weight:600;">{{ $sn }}</div>
                <div class="slider-bar"><div class="slider-fill" style="width:80%"></div></div>
            </div>
            @endif
            @endforeach
        </div>
        @endif

        @if(!empty($education) && count($education) > 0)
        <div style="margin-top:10px;">
            <div class="sec-title">Education</div>
            @foreach($education as $ed)
            <div style="margin-bottom:6px;">
                <div style="font-size:9.5px;font-weight:700;">{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div>
                <div style="font-size:8.5px;color:var(--muted)">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
        <div>
            <div class="sec-title">Languages</div>
            @foreach($languages as $l)
            @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
            @if($ln)<div style="font-size:8.5px;margin-bottom:3px;">• {{ $ln }} @if($lp)<span style="color:var(--muted);font-size:8px;">({{ $lp }})</span>@endif</div>@endif
            @endforeach
        </div>
        @endif

        @if(!empty($certifications) && count($certifications) > 0)
        <div>
            <div class="sec-title">Certifications</div>
            @foreach($certifications as $c)
            <div style="font-size:8.5px;margin-bottom:4px;"><strong>{{ $c['name'] ?? '' }}</strong>@if(!empty($c['issuer']))<div style="color:var(--muted);font-size:8px;">{{ $c['issuer'] }}</div>@endif</div>
            @endforeach
        </div>
        @endif

        @if(!empty($hobbies) && count($hobbies) > 0)
        <div>
            <div class="sec-title">Interests</div>
            <div style="font-size:8.5px;line-height:1.4;">
                @foreach($hobbies as $h)
                @php $hn = is_array($h) ? ($h['name'] ?? reset($h)) : $h; @endphp
                @if($hn)<span style="display:inline-block;background:#fed7aa;padding:1px 6px;border-radius:3px;margin:0 2px 2px 0;">{{ $hn }}</span>@endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</body>
</html>