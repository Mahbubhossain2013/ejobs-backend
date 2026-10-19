<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Analyst Resume</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --navy: #1e293b; --gold: #f59e0b; --text: #0f172a; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f172a; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .side { width: 34%; background: var(--navy); color: #fff; padding: 26px 18px; display: flex; flex-direction: column; gap: 16px; align-self: stretch; min-height: 100%; }
        .main { flex: 1; padding: 26px 26px; display: flex; flex-direction: column; gap: 16px; }
        .gold-badge { background: var(--gold); color: #000; padding: 10px 16px; border-radius: 6px; margin-bottom: 12px; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0f172a; border-bottom: 2px solid var(--gold); padding-bottom: 3px; margin-bottom: 8px; }
        .exp-item { margin-bottom: 8px; }
        .exp-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; }
        .exp-co { font-size: 9.5px; color: #d97706; font-weight: 600; margin-bottom: 2px; }
        .exp-desc { font-size: 9px; color: #475569; line-height: 1.5; }
        .exp-desc ul { padding-left: 14px; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="side">
        <div style="text-align:center;">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" style="width:78px;height:78px;border-radius:50%;border:2px solid var(--gold);object-fit:cover;" alt="">
            @endif
        </div>

        <div>
            <div style="font-size:10px;font-weight:800;color:var(--gold);text-transform:uppercase;margin-bottom:6px;">Contact</div>
            @if(!empty($candidate['phone']))<div style="font-size:9px;margin-bottom:4px;">📞 {{ $candidate['phone'] }}</div>@endif
            @if(!empty($candidate['email']))<div style="font-size:9px;margin-bottom:4px;">✉️ {{ $candidate['email'] }}</div>@endif
            @if(!empty($candidate['location']))<div style="font-size:9px;margin-bottom:4px;">📍 {{ $candidate['location'] }}</div>@endif
        </div>

        @php
            $personalInfo = [];
            if(!empty($candidate['father_name'])) $personalInfo[] = ['label' => "Father's Name", 'val' => $candidate['father_name']];
            if(!empty($candidate['mother_name'])) $personalInfo[] = ['label' => "Mother's Name", 'val' => $candidate['mother_name']];
            if(!empty($candidate['dob'])) $personalInfo[] = ['label' => 'Date of Birth', 'val' => $candidate['dob']];
            if(!empty($candidate['gender'])) $personalInfo[] = ['label' => 'Gender', 'val' => ucfirst($candidate['gender'])];
            if(!empty($candidate['marital_status'])) $personalInfo[] = ['label' => 'Marital Status', 'val' => ucfirst($candidate['marital_status'])];
            if(!empty($candidate['nationality'])) $personalInfo[] = ['label' => 'Nationality', 'val' => $candidate['nationality']];
            if(!empty($candidate['religion'])) $personalInfo[] = ['label' => 'Religion', 'val' => $candidate['religion']];
            if(!empty($candidate['blood_group'])) $personalInfo[] = ['label' => 'Blood Group', 'val' => $candidate['blood_group']];
            if(!empty($candidate['nid'])) $personalInfo[] = ['label' => 'NID', 'val' => $candidate['nid']];
        @endphp
        @if(count($personalInfo) > 0)
        <div>
            <div style="font-size:10px;font-weight:800;color:var(--gold);text-transform:uppercase;margin-bottom:6px;">Personal Details</div>
            @foreach($personalInfo as $pi)
            <div style="font-size:8.5px;margin-bottom:3px;color:#cbd5e1;"><span style="color:#94a3b8;font-weight:600;">{{ $pi['label'] }}:</span> {{ $pi['val'] }}</div>
            @endforeach
        </div>
        @endif

        @if(!empty($skills) && count($skills) > 0)
        <div>
            <div style="font-size:10px;font-weight:800;color:var(--gold);text-transform:uppercase;margin-bottom:6px;">Skills Rating</div>
            @foreach($skills as $s)
            @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
            @if($sn)<div style="font-size:9px;margin-bottom:3px;color:#f8fafc;">★ {{ $sn }}</div>@endif
            @endforeach
        </div>
        @endif

        @if(!empty($languages) && count($languages) > 0)
        <div>
            <div style="font-size:10px;font-weight:800;color:var(--gold);text-transform:uppercase;margin-bottom:6px;">Languages</div>
            @foreach($languages as $l)
            @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
            @if($ln)<div style="font-size:8.5px;margin-bottom:3px;color:#f8fafc;">• {{ $ln }} @if($lp)<span style="color:#94a3b8;font-size:8px;">({{ $lp }})</span>@endif</div>@endif
            @endforeach
        </div>
        @endif

        @if(!empty($certifications) && count($certifications) > 0)
        <div>
            <div style="font-size:10px;font-weight:800;color:var(--gold);text-transform:uppercase;margin-bottom:6px;">Certifications</div>
            @foreach($certifications as $c)
            <div style="font-size:8.5px;margin-bottom:4px;color:#f8fafc;"><strong>{{ $c['name'] ?? '' }}</strong>@if(!empty($c['issuer']))<div style="color:#94a3b8;font-size:8px;">{{ $c['issuer'] }}</div>@endif</div>
            @endforeach
        </div>
        @endif

        @if(!empty($hobbies) && count($hobbies) > 0)
        <div>
            <div style="font-size:10px;font-weight:800;color:var(--gold);text-transform:uppercase;margin-bottom:6px;">Interests</div>
            <div style="font-size:8.5px;color:#cbd5e1;line-height:1.4;">
                @foreach($hobbies as $h)
                @php $hn = is_array($h) ? ($h['name'] ?? reset($h)) : $h; @endphp
                @if($hn)<span style="display:inline-block;background:rgba(255,255,255,0.08);padding:1px 6px;border-radius:3px;margin:0 2px 2px 0;">{{ $hn }}</span>@endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="main">
        <div class="gold-badge">
            <h1 style="font-size:20px;font-weight:800;">{{ $candidate['full_name'] ?? 'Mark Brown' }}</h1>
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">{{ $candidate['title'] ?? '' }}</div>
        </div>

        @if(!empty($summary))
        <div><div class="sec-title">Profile</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
        @endif

        @if(!empty($experience) && count($experience) > 0)
        <div>
            <div class="sec-title">Professional Experience</div>
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
        @endif

        @if(!empty($education) && count($education) > 0)
        <div>
            <div class="sec-title">Education</div>
            @foreach($education as $ed)
            <div class="exp-item">
                <div class="exp-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $ed['start_date'] ?? '' }}</div></div>
                <div class="exp-co">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($projects) && count($projects) > 0)
        <div>
            <div class="sec-title">Key Projects</div>
            @foreach($projects as $p)
            <div class="exp-item">
                <div class="exp-head"><div>{{ $p['name'] ?? '' }}</div>@if(!empty($p['url']))<a href="{{ $p['url'] }}" target="_blank" style="font-size:8.5px;color:var(--gold);">Link</a>@endif</div>
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
                <div style="font-size:9px;line-height:1.4;background:#f8fafc;padding:6px;border-radius:4px;">
                    <strong>{{ $r['name'] ?? '' }}</strong>
                    @if(!empty($r['designation']))<div>{{ $r['designation'] }}{{ !empty($r['organization']) ? ', ' . $r['organization'] : '' }}</div>@endif
                    @if(!empty($r['phone']))<div>📞 {{ $r['phone'] }}</div>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</body>
</html>