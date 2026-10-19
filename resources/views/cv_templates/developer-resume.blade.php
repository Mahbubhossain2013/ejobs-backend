<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $candidate['full_name'] ?? 'CV' }} - Developer</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root { --blue: #2563eb; --dark: #0f172a; --text: #1e293b; --muted: #64748b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: var(--text); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { overflow: visible !important; }
        .cv-page { width: 210mm; min-height: auto; margin: 0 auto; background: #fff; display: flex; flex-direction: column;     align-items: stretch;
        }
        @media print { body { background: #fff; } .cv-page { width: 100%; min-height: auto; } }
        .header { background: var(--dark); color: #fff; padding: 22px 30px; display: flex; align-items: center; gap: 20px; }
        .avatar { width: 72px; height: 72px; border-radius: 8px; border: 2px solid var(--blue); overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-init { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 26px; color: var(--blue); background: #1e293b; }
        .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; padding: 24px 30px; flex: 1; }
        .sec-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--blue); border-bottom: 2px solid #dbeafe; padding-bottom: 3px; margin-bottom: 8px; }
        .card { background: #f8fafc; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; border-left: 3px solid var(--blue); }
        .card-head { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #0f172a; }
        .tag { display: inline-block; font-family: 'JetBrains Mono', monospace; font-size: 8.5px; padding: 2px 6px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 4px; margin: 0 3px 4px 0; }
    
        .item-box, .entry-block, .content-card, .timeline-item, .card-box, .exp-item, .edu-item { page-break-inside: avoid; break-inside: avoid; }
    </style>
</head>
<body>
<div class="cv-page">
    <div class="header">
        <div class="avatar">
            @if(!empty($candidate['photo_url']))
                <img src="{{ $candidate['photo_url'] }}" alt="">
            @else
                <div class="avatar-init">&lt;/&gt;</div>
            @endif
        </div>
        <div>
            <h1 style="font-size:22px;font-weight:800;letter-spacing:-0.5px;">{{ $candidate['full_name'] ?? 'PAUL JOHNSONS' }}</h1>
            <div style="font-size:10.5px;color:#93c5fd;font-weight:600;">{{ $candidate['title'] ?? '' }}</div>
            <div style="font-size:9px;color:#94a3b8;margin-top:4px;">
                @if(!empty($candidate['phone']))<span>📞 {{ $candidate['phone'] }}</span> &nbsp; @endif
                @if(!empty($candidate['email']))<span>✉️ {{ $candidate['email'] }}</span> &nbsp; @endif
                @if(!empty($candidate['location']))<span>📍 {{ $candidate['location'] }}</span>@endif
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div>
            @if(!empty($summary))
            <div><div class="sec-title">Summary</div><p style="font-size:9.5px;line-height:1.6;color:#334155;">{{ $summary }}</p></div>
            @endif

            @if(!empty($experience) && count($experience) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Experience</div>
                @foreach($experience as $e)
                <div class="card">
                    <div class="card-head"><div>{{ $e['position'] ?? $e['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $e['start_date'] ?? '' }}{{ !empty($e['end_date']) ? ' – ' . $e['end_date'] : ' – Present' }}</div></div>
                    <div style="font-size:9.5px;color:var(--blue);font-weight:600;margin:1px 0 2px;">{{ $e['company'] ?? $e['company_name'] ?? '' }}</div>
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
            <div style="margin-top:10px;">
                <div class="sec-title">Key Projects</div>
                @foreach($projects as $p)
                <div class="card" style="border-left-color:#6366f1;">
                    <div class="card-head"><div>{{ $p['name'] ?? '' }}</div>@if(!empty($p['url']))<a href="{{ $p['url'] }}" target="_blank" style="font-size:8.5px;color:var(--blue);">Link</a>@endif</div>
                    @if(!empty($p['description']))<div style="font-size:9px;color:#475569;margin-top:2px;">{{ $p['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($training) && count($training) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Training & Courses</div>
                @foreach($training as $t)
                <div class="card" style="border-left-color:#0284c7;">
                    <div class="card-head"><div>{{ $t['title'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $t['duration'] ?? '' }}</div></div>
                    <div style="font-size:9px;color:var(--muted)">{{ $t['institute'] ?? $t['institution'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($references) && count($references) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">References</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                    @foreach($references as $r)
                    <div style="font-size:9px;background:#f8fafc;padding:6px;border-radius:4px;border:1px solid #e2e8f0;">
                        <strong>{{ $r['name'] ?? '' }}</strong>
                        @if(!empty($r['designation']))<div>{{ $r['designation'] }}{{ !empty($r['organization']) ? ', ' . $r['organization'] : '' }}</div>@endif
                        @if(!empty($r['phone']))<div>📞 {{ $r['phone'] }}</div>@endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div>
            @if(!empty($education) && count($education) > 0)
            <div>
                <div class="sec-title">Education</div>
                @foreach($education as $ed)
                <div class="card" style="border-left-color:#0f172a;">
                    <div class="card-head"><div>{{ $ed['degree'] ?? $ed['qualification'] ?? '' }}</div><div style="font-size:8.5px;color:var(--muted)">{{ $ed['start_date'] ?? '' }}</div></div>
                    <div style="font-size:9.5px;color:#0f172a;font-weight:600;">{{ $ed['institution'] ?? $ed['school'] ?? '' }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($skills) && count($skills) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Tech Stack</div>
                <div>
                    @foreach($skills as $s)
                    @php $sn = is_array($s) ? ($s['name'] ?? $s['label'] ?? '') : $s; @endphp
                    @if($sn)<span class="tag">{{ $sn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @php
                $devPersonalInfo = [];
                if(!empty($candidate['father_name'])) $devPersonalInfo[] = ['label' => "Father's Name", 'val' => $candidate['father_name']];
                if(!empty($candidate['mother_name'])) $devPersonalInfo[] = ['label' => "Mother's Name", 'val' => $candidate['mother_name']];
                if(!empty($candidate['dob'])) $devPersonalInfo[] = ['label' => 'Date of Birth', 'val' => $candidate['dob']];
                if(!empty($candidate['gender'])) $devPersonalInfo[] = ['label' => 'Gender', 'val' => ucfirst($candidate['gender'])];
                if(!empty($candidate['marital_status'])) $devPersonalInfo[] = ['label' => 'Marital Status', 'val' => ucfirst($candidate['marital_status'])];
                if(!empty($candidate['nationality'])) $devPersonalInfo[] = ['label' => 'Nationality', 'val' => $candidate['nationality']];
                if(!empty($candidate['religion'])) $devPersonalInfo[] = ['label' => 'Religion', 'val' => $candidate['religion']];
                if(!empty($candidate['blood_group'])) $devPersonalInfo[] = ['label' => 'Blood Group', 'val' => $candidate['blood_group']];
                if(!empty($candidate['nid'])) $devPersonalInfo[] = ['label' => 'NID', 'val' => $candidate['nid']];
            @endphp
            @if(count($devPersonalInfo) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Personal Details</div>
                <div class="card" style="border-left-color:#64748b;">
                    @foreach($devPersonalInfo as $pi)
                    <div style="font-size:8.5px;margin-bottom:3px;color:#334155;"><strong style="color:#0f172a;">{{ $pi['label'] }}:</strong> {{ $pi['val'] }}</div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($languages) && count($languages) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Languages</div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    @foreach($languages as $l)
                    @php $ln = is_array($l) ? ($l['name'] ?? $l['language'] ?? '') : $l; $lp = is_array($l) ? ($l['proficiency'] ?? $l['level'] ?? '') : ''; @endphp
                    @if($ln)<span class="tag" style="background:#f1f5f9;border-color:#cbd5e1;color:#334155;">{{ $ln }} @if($lp)({{ $lp }})@endif</span>@endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($certifications) && count($certifications) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Certifications</div>
                @foreach($certifications as $c)
                <div class="card" style="border-left-color:#10b981;margin-bottom:5px;">
                    <div style="font-size:9.5px;font-weight:700;">{{ $c['name'] ?? '' }}</div>
                    @if(!empty($c['issuer']))<div style="font-size:8.5px;color:var(--muted)">{{ $c['issuer'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($hobbies) && count($hobbies) > 0)
            <div style="margin-top:10px;">
                <div class="sec-title">Interests</div>
                <div>
                    @foreach($hobbies as $h)
                    @php $hn = is_array($h) ? ($h['name'] ?? reset($h)) : $h; @endphp
                    @if($hn)<span class="tag" style="background:#f8fafc;border-color:#e2e8f0;color:#64748b;">{{ $hn }}</span>@endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>