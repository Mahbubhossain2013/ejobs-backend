@php
    $template = $template ?? null;
    if (!$template) return;
    $orientation = $template->orientation === 'portrait' ? 'h-[420px] w-[300px]' : 'h-[280px] w-[420px]';
    $bg = $template->background_color ?: '#ffffff';
    $primary = $template->primary_color ?: '#2563eb';
    $accent = $template->accent_color ?: '#f59e0b';
    $logo = $template->logo_path ? asset('storage/' . $template->logo_path) : null;
    $watermark = $template->watermark_path ? asset('storage/' . $template->watermark_path) : null;
    $name = 'Sample Candidate Name';
    $course = 'Sample Course Title';
    $date = now()->format('F d, Y');
    $certId = 'CERT-XXXX-XXXX-00001';
@endphp

<div class="flex justify-center py-6">
    <div class="{{ $orientation }} relative rounded-xl shadow-2xl overflow-hidden flex flex-col items-center justify-between p-6 text-center" style="background-color: {{ $bg }};">
        
        @if($watermark)
            <img src="{{ $watermark }}" class="absolute inset-0 w-full h-full object-cover opacity-10 pointer-events-none" alt="">
        @endif

        <div class="relative z-10 w-full">
            @if($logo)
                <img src="{{ $logo }}" class="h-12 mx-auto mb-2 object-contain" alt="Logo">
            @else
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-white text-2xl font-bold mb-2" style="background-color: {{ $primary }};">
                    {{ substr(config('app.name', 'eJob'), 0, 1) }}
                </div>
            @endif

            <h2 class="text-xl font-bold tracking-wide" style="color: {{ $primary }};">CERTIFICATE</h2>
            <div class="h-1 w-16 mx-auto mt-1 rounded-full" style="background-color: {{ $accent }};"></div>
        </div>

        <div class="relative z-10 space-y-1">
            <p class="text-xs uppercase tracking-widest" style="color: {{ $primary }};">This is to certify that</p>
            <h3 class="text-lg font-bold" style="color: {{ $primary }};">{{ $name }}</h3>
            <p class="text-xs" style="color: #6b7280;">has successfully completed the course</p>
            <p class="text-sm font-semibold" style="color: {{ $primary }};">{{ $course }}</p>
        </div>

        <div class="relative z-10 w-full flex items-end justify-between pt-3 border-t" style="border-color: {{ $primary }}20;">
            <div class="text-left">
                <p class="text-[10px] uppercase tracking-wider" style="color: {{ $primary }};">Date</p>
                <p class="text-xs font-medium">{{ $date }}</p>
            </div>
            <div class="text-center">
                <div class="h-8 w-16 rounded" style="background-color: {{ $primary }}20;"></div>
                <p class="text-[10px] mt-0.5" style="color: {{ $primary }};">Signature</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-wider" style="color: {{ $primary }};">ID</p>
                <p class="text-[10px] font-mono">{{ $certId }}</p>
            </div>
        </div>
    </div>
</div>
