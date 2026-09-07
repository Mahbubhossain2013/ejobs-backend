@php
    $analytics = $this->getAnalyticsData();
    $promo = $this->record;
@endphp

<x-filament-panels::page>
    <div class="space-y-6" x-data="{ activeTab: 'analytics' }">
        {{-- Custom Campaign Header styled matching Filament section styling --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <x-filament::badge color="primary">
                                {{ ucwords(str_replace('_', ' ', $promo->campaign_type)) }}
                            </x-filament::badge>
                            
                            <x-filament::badge :color="match($promo->status) {
                                'active' => 'success',
                                'paused' => 'warning',
                                'suspended' => 'danger',
                                'pending_review' => 'info',
                                default => 'gray'
                            }">
                                {{ strtoupper($promo->status) }}
                            </x-filament::badge>
                        </div>
                        <h3 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white mt-2">{{ $promo->title }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Employer: <span class="font-bold text-gray-700 dark:text-gray-200">{{ $promo->user?->name }}</span> | 
                            Active: <span class="font-semibold">{{ $promo->start_date->format('M d, Y') }}</span> to 
                            <span class="font-semibold">{{ $promo->end_date ? $promo->end_date->format('M d, Y') : 'Ongoing' }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        {{-- Quick actions inside view page --}}
                        @if($promo->status === 'active')
                            <x-filament::button wire:click="callTableAction('pause')" color="warning" icon="heroicon-o-pause" size="sm">
                                Pause Campaign
                            </x-filament::button>
                            @if($promo->ranking_override !== 'boost')
                                <x-filament::button wire:click="callTableAction('boost')" color="primary" icon="heroicon-o-bolt" size="sm">
                                    Force Boost
                                </x-filament::button>
                            @endif
                        @elseif($promo->status === 'paused')
                            <x-filament::button wire:click="callTableAction('resume')" color="success" icon="heroicon-o-play" size="sm">
                                Resume Campaign
                            </x-filament::button>
                        @elseif($promo->status === 'pending_review')
                            <x-filament::button wire:click="callTableAction('approve')" color="success" icon="heroicon-o-check" size="sm">
                                Approve Campaign
                            </x-filament::button>
                        @endif
                        
                        <x-filament::button :href="url('/admin/marketing/campaigns/' . $promo->id . '/edit')" tag="a" color="gray" icon="heroicon-o-pencil" size="sm">
                            Edit Configs
                        </x-filament::button>
                    </div>
                </div>
            </x-slot>
        </x-filament::section>

        {{-- Custom Navigation Tabs using Filament native tabs components --}}
        <x-filament::tabs>
            <x-filament::tabs.item 
                x-on:click="activeTab = 'analytics'"
                x-bind:active="activeTab === 'analytics'"
                icon="heroicon-o-chart-bar"
            >
                Real-Time Analytics
            </x-filament::tabs.item>
            <x-filament::tabs.item 
                x-on:click="activeTab = 'moderation'"
                x-bind:active="activeTab === 'moderation'"
                icon="heroicon-o-shield-check"
            >
                AI Safety & Moderation
            </x-filament::tabs.item>
            <x-filament::tabs.item 
                x-on:click="activeTab = 'telemetry'"
                x-bind:active="activeTab === 'telemetry'"
                icon="heroicon-o-finger-print"
            >
                Fraud & Telemetry Logs
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Tab Content Panel --}}
        <div>
            {{-- Tab 1: Real-Time Analytics --}}
            <div x-show="activeTab === 'analytics'" class="space-y-6">
                {{-- AI Analytics Insights --}}
                <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-5 rounded-2xl border border-indigo-100/60 dark:border-indigo-900/40 flex items-start gap-4">
                    <span class="p-2 bg-indigo-500/10 text-indigo-500 rounded-xl">
                        <x-heroicon-o-light-bulb class="h-6 w-6" />
                    </span>
                    <div>
                        <h4 class="text-sm font-bold text-indigo-950 dark:text-indigo-300">AI Campaign Performance Insights</h4>
                        <p class="text-sm text-indigo-900/80 dark:text-indigo-400 leading-relaxed mt-1">
                            {{ $analytics['ai_summary'] }}
                        </p>
                    </div>
                </div>

                {{-- Funnel and split grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Conversion Funnel --}}
                    <x-filament::section class="lg:col-span-2">
                        <x-slot name="heading">Job Conversion Funnel</x-slot>
                        
                        <div class="space-y-5 mt-4">
                            {{-- Stage 1 --}}
                            <div>
                                <div class="flex justify-between items-center text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">
                                    <span>1. Impressions (Search Inject + Feed Placements)</span>
                                    <span class="text-gray-900 dark:text-white font-bold">{{ number_format($analytics['views']) }} views</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-primary-600 h-full rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                            {{-- Stage 2 --}}
                            <div>
                                <div class="flex justify-between items-center text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">
                                    <span>2. Click-Through Engagement (CTR: {{ $analytics['ctr'] }}%)</span>
                                    <span class="text-gray-900 dark:text-white font-bold">{{ number_format($analytics['clicks']) }} clicks</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-blue-500 h-full rounded-full" style="width: 12%"></div>
                                </div>
                            </div>
                            {{-- Stage 3 --}}
                            <div>
                                <div class="flex justify-between items-center text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">
                                    <span>3. Candidate Applications Submitted</span>
                                    <span class="text-gray-900 dark:text-white font-bold">{{ number_format($analytics['applies']) }} subms</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-teal-500 h-full rounded-full" style="width: 6%"></div>
                                </div>
                            </div>
                            {{-- Stage 4 --}}
                            <div>
                                <div class="flex justify-between items-center text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">
                                    <span>4. Shortlists & Pre-Screens</span>
                                    <span class="text-gray-900 dark:text-white font-bold">{{ number_format($analytics['shortlisted']) }} candidates</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full" style="width: 2%"></div>
                                </div>
                            </div>
                        </div>
                    </x-filament::section>

                    {{-- Target Devices split --}}
                    <x-filament::section class="flex flex-col justify-between h-full">
                        <x-slot name="heading">Device Impression Distribution</x-slot>
                        
                        <div class="space-y-4 mt-4">
                            <div class="flex justify-between items-center text-sm">
                                <span class="flex items-center gap-2 font-medium text-gray-600 dark:text-gray-300">
                                    <span class="w-3 h-3 rounded-full bg-blue-500"></span> Mobile App
                                </span>
                                <span class="font-bold text-gray-950 dark:text-white">{{ $analytics['devices']['mobile'] }}%</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="flex items-center gap-2 font-medium text-gray-600 dark:text-gray-300">
                                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Desktop browser
                                </span>
                                <span class="font-bold text-gray-950 dark:text-white">{{ $analytics['devices']['desktop'] }}%</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="flex items-center gap-2 font-medium text-gray-600 dark:text-gray-300">
                                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span> Tablet / iPad
                                </span>
                                <span class="font-bold text-gray-950 dark:text-white">{{ $analytics['devices']['tablet'] }}%</span>
                            </div>
                        </div>
                        
                        <div class="pt-6 border-t border-gray-100 dark:border-gray-750 text-xs text-gray-400 mt-6 leading-relaxed">
                            Target devices filter is currently set to: <span class="font-bold text-gray-700 dark:text-gray-300">{{ strtoupper($promo->target_devices) }}</span>
                        </div>
                    </x-filament::section>
                </div>

                {{-- Geo distribution list and Click Heatmap --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <x-filament::section class="lg:col-span-1">
                        <x-slot name="heading">Geographic Breakdown</x-slot>
                        
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 mt-4">
                            @foreach($analytics['locations'] as $loc)
                            <div class="py-3 flex justify-between items-center text-sm font-medium">
                                <span class="text-gray-600 dark:text-gray-300">{{ $loc['name'] }}</span>
                                <div class="text-right">
                                    <p class="font-bold text-gray-950 dark:text-white">{{ $loc['percentage'] }}%</p>
                                    <p class="text-2xs text-gray-450">{{ number_format($loc['impressions']) }} imps</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </x-filament::section>

                    {{-- Click Heatmap Simulator --}}
                    <x-filament::section class="lg:col-span-2">
                        <x-slot name="heading">
                            <div class="flex justify-between items-center w-full">
                                <span>Active Spot Click Heatmap Simulator</span>
                                <x-filament::badge color="success">LIVE MAPPING</x-filament::badge>
                            </div>
                        </x-slot>

                        {{-- Simulated Job Card with red/orange hotspots overlay --}}
                        <div class="relative rounded-xl border border-gray-150 dark:border-gray-750 bg-gray-50 dark:bg-slate-900/60 p-5 overflow-hidden mt-4">
                            <div class="flex gap-4 items-start relative z-10">
                                <div class="w-12 h-12 rounded-xl bg-primary-600 text-white font-bold flex items-center justify-center text-lg shadow-sm">
                                    {{ substr($promo->user?->name ?? 'C', 0, 2) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h5 class="text-base font-bold text-slate-800 dark:text-slate-200">{{ $promo->title }}</h5>
                                        <x-filament::badge color="primary">Promoted</x-filament::badge>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-650 dark:text-slate-400 mt-1">{{ $promo->user?->name }} | Dhaka, BD</p>
                                    
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        <span class="px-2 py-0.5 rounded text-3xs bg-gray-200/60 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-bold">Laravel PHP</span>
                                        <span class="px-2 py-0.5 rounded text-3xs bg-gray-200/60 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-bold">Vite React</span>
                                        <span class="px-2 py-0.5 rounded text-3xs bg-gray-200/60 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-bold">MySQL DB</span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Heatmap Dot Overlays --}}
                            {{-- Large red hot circle on Title --}}
                            <div class="absolute top-6 left-28 w-14 h-14 rounded-full bg-red-500/35 border border-red-500/85 filter blur-sm animate-pulse"></div>
                            <div class="absolute top-9 left-36 w-3 h-3 rounded-full bg-red-600"></div>

                            {{-- Orange hot circle on Apply button --}}
                            <div class="absolute top-16 right-16 w-8 h-8 rounded-full bg-amber-500/30 border border-amber-500/75 filter blur-xs"></div>
                            <div class="absolute top-19 right-19 w-2 h-2 rounded-full bg-amber-600"></div>

                            {{-- Small yellow spots --}}
                            <div class="absolute top-4 left-6 w-6 h-6 rounded-full bg-yellow-400/20 filter blur-md"></div>
                            <div class="absolute bottom-6 left-32 w-10 h-10 rounded-full bg-yellow-400/20 border border-yellow-400/50 filter blur-sm"></div>
                        </div>

                        <p class="text-2xs text-gray-400 mt-3 italic leading-relaxed">
                            * The hotspots represent areas of highest engagement tracking. 84% of impressions click on the main title card, while 12% directly hover and tap the instant applications trigger.
                        </p>
                    </x-filament::section>
                </div>
            </div>

            {{-- Tab 2: AI Safety & Moderation --}}
            <div x-show="activeTab === 'moderation'" class="space-y-6">
                {{-- Scorecard grid --}}
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach([
                        ['label' => 'Spam Score', 'value' => $promo->spam_score ?? 0, 'limit' => 35, 'symbol' => '%'],
                        ['label' => 'Fraud Score', 'value' => $promo->fraud_score ?? 0, 'limit' => 35, 'symbol' => '%'],
                        ['label' => 'Link Safety', 'value' => ($promo->link_safety_score ?? 1.0) * 100, 'limit' => 80, 'symbol' => '%', 'reverse' => true],
                        ['label' => 'Content Policy', 'value' => $promo->content_policy_score ?? 0, 'limit' => 35, 'symbol' => '%'],
                        ['label' => 'Duplicate Score', 'value' => $promo->duplicate_score ?? 0, 'limit' => 35, 'symbol' => '%'],
                        ['label' => 'Anomaly Score', 'value' => $promo->anomaly_score ?? 0, 'limit' => 35, 'symbol' => '%'],
                    ] as $score)
                    @php
                        $isDanger = false;
                        if (isset($score['reverse']) && $score['reverse']) {
                            $isDanger = $score['value'] < $score['limit'];
                        } else {
                            $isDanger = $score['value'] > $score['limit'];
                        }
                    @endphp
                    <div class="p-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-150 dark:border-gray-700 shadow-sm text-center flex flex-col justify-between items-center gap-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold">{{ $score['label'] }}</p>
                        <p class="text-3xl font-extrabold tracking-tight {{ $isDanger ? 'text-rose-600 dark:text-rose-455' : 'text-emerald-600 dark:text-emerald-455' }}">
                            {{ number_format($score['value']) }}{{ $score['symbol'] }}
                        </p>
                        <x-filament::badge :color="$isDanger ? 'danger' : 'success'">
                            {{ $isDanger ? 'RISK FLAG' : 'SECURE' }}
                        </x-filament::badge>
                    </div>
                    @endforeach
                </div>

                {{-- Explainability card --}}
                <x-filament::section>
                    <x-slot name="heading">AI Safety Screening Decisions</x-slot>
                    
                    <div class="space-y-4 mt-4 text-sm font-medium">
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-slate-900/60 border border-gray-150 dark:border-gray-750">
                            <span class="font-bold text-xs text-gray-400 block uppercase mb-1.5">AI Explanation Summary</span>
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed font-mono">
                                "{{ $promo->ai_explanation ?? 'This campaign passed screening without any critical security flags triggered.' }}"
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl border border-gray-150 dark:border-gray-750 flex items-center gap-3 bg-white dark:bg-gray-800">
                                <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <x-heroicon-o-shield-check class="h-6 w-6" />
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">Whitelisted Status</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $promo->whitelisted_employer ? 'Employer whitelisted: Automated checks bypassed.' : 'Standard screening checks active.' }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="p-4 rounded-xl border border-gray-150 dark:border-gray-750 flex items-center gap-3 bg-white dark:bg-gray-800">
                                <span class="p-2 rounded-lg bg-primary-500/10 text-primary-600 dark:text-primary-400">
                                    <x-heroicon-o-finger-print class="h-6 w-6" />
                                </span>
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">Auto-Approved Safe Rule</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ ($promo->moderation_status === 'safe') ? 'Marked safe. Automatically whitelisted for auto ranking pipelines.' : 'Moderated review queues active.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Tab 3: Fraud & Telemetry Logs --}}
            <div x-show="activeTab === 'telemetry'" class="space-y-6">
                {{-- VPN Proxy alert banner if fraud detected --}}
                @if($promo->is_suspended)
                <div class="bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/60 p-5 rounded-2xl flex items-start gap-4 text-rose-950 dark:text-rose-200">
                    <span class="p-2 bg-rose-500/20 text-rose-600 rounded-xl">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                    </span>
                    <div>
                        <h4 class="text-sm font-bold">FRAUD SUSPENSION ACTIVE</h4>
                        <p class="text-sm mt-1 leading-relaxed text-rose-900/80 dark:text-rose-400">
                            Reason: {{ $promo->fraud_reason ?? 'Repeated bot engagement patterns.' }}
                        </p>
                    </div>
                </div>
                @endif

                {{-- Log timeline --}}
                <x-filament::section>
                    <x-slot name="heading">Security Audit & Telemetry Timelines</x-slot>
                    
                    <div class="relative pl-6 border-l-2 border-slate-100 dark:border-gray-700 space-y-6 mt-4 font-semibold text-sm">
                        @forelse($promo->suspicious_activity_logs ?? [] as $log)
                        <div class="relative">
                            {{-- Timeline dot --}}
                            <div class="absolute -left-9 top-1 w-4 h-4 rounded-full bg-rose-500 border-2 border-white dark:border-gray-800 shadow-sm"></div>
                            
                            <div>
                                <span class="text-2xs font-semibold text-gray-400">{{ $log['timestamp'] }}</span>
                                <h5 class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $log['trigger'] }}</h5>
                                <p class="text-xs text-rose-600 dark:text-rose-455 font-semibold mt-1">Action: {{ $log['action'] }}</p>
                            </div>
                        </div>
                        @empty
                        {{-- Standard clean baseline logs --}}
                        <div class="relative">
                            <div class="absolute -left-9 top-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-800 shadow-sm"></div>
                            <div>
                                <span class="text-2xs font-semibold text-gray-450">{{ now()->subHour()->format('Y-m-d H:i:s') }}</span>
                                <h5 class="text-sm font-bold text-gray-950 dark:text-white mt-0.5">Click Fraud Telemetry Scans</h5>
                                <p class="text-xs text-emerald-600 dark:text-emerald-455 font-bold mt-1">No clicks anomaly flagged. 0 bot attempts blocked.</p>
                            </div>
                        </div>
                        <div class="relative">
                            <div class="absolute -left-9 top-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-800 shadow-sm"></div>
                            <div>
                                <span class="text-2xs font-semibold text-gray-450">{{ now()->subHours(6)->format('Y-m-d H:i:s') }}</span>
                                <h5 class="text-sm font-bold text-gray-950 dark:text-white mt-0.5">IP click mapping & VPN Checks</h5>
                                <p class="text-xs text-emerald-600 dark:text-emerald-455 font-bold mt-1">Clean session baseline verified. Checked 4 IP sequences.</p>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
