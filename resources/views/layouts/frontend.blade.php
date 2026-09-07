<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\Setting::where('key', 'site_name')->value('value') ?? 'JobBazar' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @php
        $primaryColor = \App\Models\Setting::where('key', 'primary_color')->value('value') ?? '#5A67D8';
    @endphp

    <style>
        :root { --primary-color: {{ $primaryColor }}; }
        .bg-primary { background-color: var(--primary-color); }
        .text-primary { color: var(--primary-color); }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-primary">JobBazar</a>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="/jobs" class="text-gray-900 px-3 py-2 text-sm font-medium">Browse Jobs</a>
                        <a href="#" class="text-gray-500 px-3 py-2 text-sm font-medium">Companies</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="/dashboard" class="text-sm font-medium text-gray-700">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700">Login</a>
                        <a href="{{ route('register') }}" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium">Post a Job</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @php
        $settings = \App\Models\Setting::all()->keyBy('key');
        $copyrightText = $settings->get('footer_copyright_text')?->value;
        $siteName = $settings->get('site_name')?->value ?? config('app.name', 'Job Portal');
        $creditEnabled = $settings->get('footer_credit_enabled')?->value ?? '1';
        $creditText = $settings->get('footer_credit_text')?->value ?? 'Developed by NEXTIN';
        $creditUrl = $settings->get('footer_credit_url')?->value ?? 'https://nextin.fobign.com';
    @endphp
    <footer class="bg-white border-t mt-12 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 text-sm">
            <p>{!! $copyrightText ?: '&copy; ' . date('Y') . ' ' . $siteName . '. All rights reserved.' !!}</p>
            @if($creditEnabled === '1')
                <p class="mt-1 text-xs text-gray-400">
                    <a href="{{ $creditUrl }}" target="_blank" class="underline hover:text-primary-500 transition-colors">{{ $creditText }}</a>
                </p>
            @endif
        </div>
    </footer>
</body>
</html>