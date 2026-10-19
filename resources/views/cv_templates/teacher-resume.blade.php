<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Teacher Resume</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --teal: #14b8a6; --teal-bg: #ccfbf1; --coral: #f97316; --coral-bg: #ffedd5; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0fdfa; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .header { display: flex; align-items: center; justify-content: space-between; padding: 24px 30px; border-bottom: 2px solid #e2e8f0; background: #fff; }
        .avatar { width: 78px; height: 78px; border-radius: 50%; border: 3px solid var(--coral); overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; color: #fff; background: var(--coral); }
        .name-box h1 { font-family: 'Fredoka', sans-serif; font-size: 26px; font-weight: 700; color: #0f172a; }
        .name-box .title { font-size: 11px; color: var(--coral); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .body-wrap { display: flex; flex: 1; }
        .left-col { width: 45%; background: var(--teal-bg); padding: 22px 20px; display: flex; flex-direction: column; gap: 16px; border-radius: 0 0 0 20px; }
        .right-col { flex: 1; background: var(--coral-bg); padding: 22px 22px; display: flex; flex-direction: column; gap: 16px; border-radius: 0 0 20px 0; }
        .sec-title-left { font-family: 'Fredoka', sans-serif; font-size: 13px; font-weight: 700; color: #0f766e; text-transform: uppercase; border-bottom: 2px solid #5eead4; padding-bottom: 3px; margin-bottom: 8px; }
        .sec-title-right { font-family: 'Fredoka', sans-serif; font-size: 13px; font-weight: 700; color: #c2410c; text-transform: uppercase; border-bottom: 2px solid #fdba74; padding-bottom: 3px; margin-bottom: 8px; }
        .card-white { background: #fff; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
        .card-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #0f172a; }
        .tag { display: inline-block; font-size: 8.5px; padding: 2px 8px; background: #fff; border-radius: 12px; color: #0f766e; font-weight: 600; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <div class="name-box">
            <h1>{{ $candidate['full_name'] ?? 'Jonathan Smith' }}</h1>
            <div class="title">{{ $candidate['title'] ?? '' }}</div>
            <div style="font-size:9.5px;color:#64748b;margin-top:6px;">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span> &nbsp; @endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span> &nbsp; @endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
            </div>
        </div>
        <div class="avatar">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="">
            @else
                <div class="avatar-init">{{ strtoupper(substr($candidate['full_name'] ?? 'J', 0, 1)) }}</div>
            @endif
        </div>
    </div>
    <div class="body-wrap">
        <div class="left-col">
            @if(!empty($summary))
            <div><div class="sec-title-left">Profile</div><div class="card-white"><p style="font-size:9.5px;line-height:1.55;color:#334155;">{{ $summary }}</p></div></div>
            @endif

            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-title-left">Education</div>
                @foreach($education as $ed)
                <div class="card-white">
                    <div class="card-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $ed['start_date'] ?? '' }}</div></div>
                    <div style="font-size:9.5px;color:#0f766e;font-weight:600;margin-top:2px;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div>
                <div class="sec-title-left">Skills</div>
                <div>
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span class="tag">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @php
                $teachPersonalInfo = [];
                if(!empty($candidate['father_name'])) $teachPersonalInfo[] = ['label' => "Father's Name", 'val' => $candidate['father_name']];
                if(!empty($candidate['mother_name'])) $teachPersonalInfo[] = ['label' => "Mother's Name", 'val' => $candidate['mother_name']];
                if(!empty($candidate['dob'])) $teachPersonalInfo[] = ['label' => 'Date of Birth', 'val' => $candidate['dob']];
                if(!empty($candidate['gender'])) $teachPersonalInfo[] = ['label' => 'Gender', 'val' => ucfirst($candidate['gender'])];
                if(!empty($candidate['marital_status'])) $teachPersonalInfo[] = ['label' => 'Marital Status', 'val' => ucfirst($candidate['marital_status'])];
                if(!empty($candidate['nationality'])) $teachPersonalInfo[] = ['label' => 'Nationality', 'val' => $candidate['nationality']];
                if(!empty($candidate['religion'])) $teachPersonalInfo[] = ['label' => 'Religion', 'val' => $candidate['religion']];
                if(!empty($candidate['blood_group'])) $teachPersonalInfo[] = ['label' => 'Blood Group', 'val' => $candidate['blood_group']];
                if(!empty($candidate['nid'])) $teachPersonalInfo[] = ['label' => 'NID', 'val' => $candidate['nid']];
            @endphp
            @if(count($teachPersonalInfo) > 0)
            <div>
                <div class="sec-title-left">Personal Details</div>
                <div class="card-white" style="font-size:8.5px;line-height:1.45;">
                    @foreach($teachPersonalInfo as $pi)
                    <div style="margin-bottom:2px;"><strong style="color:#0f766e;">{{ $pi['label'] }}:</strong> {{ $pi['val'] }}</div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div>
                <div class="sec-title-left">Languages</div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    @foreach($languages as $l)
                    @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                    @if($ln)<span class="tag" style="background:#ccfbf1;color:#0f766e;">{{ $ln }} @if($lp)({{ $lp }})@endif</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($hobbies) && count($hobbies) > 0)
            <div>
                <div class="sec-title-left">Interests</div>
                <div>
                    @foreach($hobbies as $h)
                    @php $hn = is_array($h) ? ($h['name'] ?? reset($h)) : $h; @endphp
                    @if($hn)<span class="tag" style="background:#fff;color:#0f766e;">{{ $hn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="right-col">
            @if(!empty($experience) && count($experience) > 0)
            <div>
                <div class="sec-title-right">Work Experience</div>
                @foreach($experience as $e)
                <div class="card-white">
                    <div class="card-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                    <div style="font-size:9.5px;color:#c2410c;font-weight:600;margin:1px 0 3px;">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
                    @if(!empty($e['description']))
                    <div style="font-size:9px;color:#475569;line-height:1.5;">
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

            @if(!empty($projects) && count($projects) > 0)
            <div>
                <div class="sec-title-right">Key Projects</div>
                @foreach($projects as $p)
                <div class="card-white">
                    <div class="card-head"><div>{{ $p['name'] ?? '' }}</div>@if(!empty($p['url']))<a href="{{ $p['url'] }}" target="_blank" style="font-size:8.5px;color:var(--coral);">Link</a>@endif</div>
                    @if(!empty($p['description']))<div style="font-size:9px;color:#475569;margin-top:2px;">{{ $p['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($certifications) && count($certifications) > 0)
            <div>
                <div class="sec-title-right">Certifications</div>
                @foreach($certifications as $c)
                <div class="card-white" style="margin-bottom:6px;">
                    <div style="font-size:9.5px;font-weight:700;">{{ $c['name'] ?? '' }}</div>
                    @if(!empty($c['issuer']))<div style="font-size:8.5px;color:var(--muted);">{{ $c['issuer'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($training) && count($training) > 0)
            <div>
                <div class="sec-title-right">Training & Workshops</div>
                @foreach($training as $t)
                <div class="card-white" style="margin-bottom:6px;">
                    <div class="card-head"><div>{{ $t['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $t['duration'] ?? '' }}</div></div>
                    <div style="font-size:9px;color:var(--muted);">{{ $t['institute'] ?? $t['institution'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($references) && count($references) > 0)
            <div>
                <div class="sec-title-right">References</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                    @foreach($references as $r)
                    <div class="card-white" style="font-size:8.5px;line-height:1.4;">
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
</div>
</body>
</html>