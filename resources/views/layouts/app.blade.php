<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    
        <!-- Dynamic Theme Styling -->
    <style>
        :root {
            --primary-color: {{ \App\Models\Setting::where('key', 'primary_color')->value('value') ?? '#5A67D8' }};
            --button-bg: {{ \App\Models\Setting::where('key', 'button_bg')->value('value') ?? '#5A67D8' }};
            --nav-bg: {{ \App\Models\Setting::where('key', 'nav_bg')->value('value') ?? '#ffffff' }};
        }
        
        .btn-primary { background-color: var(--button-bg); }
        .nav-main { background-color: var(--nav-bg); }
    </style>
    @php
    $engFont = \App\Models\Setting::where('key', 'english_font')->value('value') ?? 'Inter';
    $bnFont = \App\Models\Setting::where('key', 'bangla_font')->value('value') ?? 'Hind Siliguri';

    $customBnFonts = [
        'SolaimanLipi' => 'https://fonts.maateen.me/solaiman-lipi/font.css',
        'Kalpurush' => 'https://fonts.maateen.me/kalpurush/font.css',
    ];
    $isCustomBnFont = array_key_exists($bnFont, $customBnFonts);
@endphp

<!-- Load English Font from Google -->
<link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $engFont) }}:wght@400;600&display=swap" rel="stylesheet">

@if($isCustomBnFont)
    <!-- Load Custom Bangla Font from CDN -->
    <link href="{{ $customBnFonts[$bnFont] }}" rel="stylesheet">
@else
    <!-- Load Bangla Font from Google -->
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $bnFont) }}:wght@400;600&display=swap" rel="stylesheet">
@endif

<style>
    body { font-family: '{{ $engFont }}', sans-serif; }
    .bangla-text { font-family: '{{ $bnFont }}', sans-serif; }
</style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
