<x-filament-panels::page>
    @php
        $guideData = $this->getGuideData();
        $allItems = collect($guideData)->flatMap(fn($s) => $s['items']);
        $working = $allItems->where('status', 'working')->count();
        $partial = $allItems->where('status', 'partial')->count();
        $missing = $allItems->where('status', 'missing')->count();
        $total = $allItems->count();
        $workingPct = $total > 0 ? round($working / $total * 100) : 0;
        $partialPct = $total > 0 ? round($partial / $total * 100) : 0;
        $missingPct = $total > 0 ? round($missing / $total * 100) : 0;
    @endphp

    <div class="space-y-8" x-data="{ statusFilter: 'all' }">

        {{-- ─── Hero Banner ─── --}}
        <div class="rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 p-8 md:p-10 shadow-lg shadow-primary-500/20">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="text-white">
                    <h1 class="text-2xl md:text-3xl font-bold tracking-tight">
                        Admin Configuration Guide
                    </h1>
                    <p class="mt-2 text-primary-100 max-w-xl">
                        Track feature integration status across your platform. Configure each setting to ensure the frontend reflects your admin changes.
                    </p>
                </div>
                <div class="flex items-center gap-8 text-white">
                    <div class="text-center">
                        <p class="text-3xl font-bold tabular-nums">{{ $workingPct }}%</p>
                        <p class="text-xs text-primary-200 mt-0.5">Complete</p>
                    </div>
                    <div class="w-px h-12 bg-white/20 hidden sm:block"></div>
                    <div class="text-center">
                        <p class="text-3xl font-bold tabular-nums">{{ $total }}</p>
                        <p class="text-xs text-primary-200 mt-0.5">Features</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Stats Cards ─── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total --}}
            <x-filament::section class="!rounded-xl">
                <div class="flex items-center gap-4 p-1">
                    <div class="flex-shrink-0 w-11 h-11 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <x-heroicon-o-bars-3-bottom-left class="w-5 h-5 text-gray-500" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $total }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Total Features</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Working --}}
            <x-filament::section class="!rounded-xl">
                <div class="flex items-center gap-4 p-1">
                    <div class="flex-shrink-0 w-11 h-11 rounded-lg bg-success-50 dark:bg-success-500/10 flex items-center justify-center">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl font-bold tabular-nums text-success-600 dark:text-success-400">{{ $working }}</p>
                            <span class="text-xs text-success-500 font-medium">{{ $workingPct }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full mt-1.5 overflow-hidden">
                            <div class="h-full bg-success-500 rounded-full transition-all" style="width: {{ $workingPct }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Connected</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Partial --}}
            <x-filament::section class="!rounded-xl">
                <div class="flex items-center gap-4 p-1">
                    <div class="flex-shrink-0 w-11 h-11 rounded-lg bg-warning-50 dark:bg-warning-500/10 flex items-center justify-center">
                        <x-heroicon-o-exclamation-circle class="w-5 h-5 text-warning-500" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl font-bold tabular-nums text-warning-600 dark:text-warning-400">{{ $partial }}</p>
                            <span class="text-xs text-warning-500 font-medium">{{ $partialPct }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full mt-1.5 overflow-hidden">
                            <div class="h-full bg-warning-500 rounded-full transition-all" style="width: {{ $partialPct }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Partially Connected</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- Missing --}}
            <x-filament::section class="!rounded-xl">
                <div class="flex items-center gap-4 p-1">
                    <div class="flex-shrink-0 w-11 h-11 rounded-lg bg-danger-50 dark:bg-danger-500/10 flex items-center justify-center">
                        <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl font-bold tabular-nums text-danger-600 dark:text-danger-400">{{ $missing }}</p>
                            <span class="text-xs text-danger-500 font-medium">{{ $missingPct }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full mt-1.5 overflow-hidden">
                            <div class="h-full bg-danger-500 rounded-full transition-all" style="width: {{ $missingPct }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Not Connected</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- ─── Overall Progress ─── --}}
        <x-filament::section class="!rounded-xl">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-500" />
                    <span>Frontend Integration Progress</span>
                </div>
            </x-slot>
            <x-slot name="description">
                {{ $working }} of {{ $total }} features are fully connected to the frontend
            </x-slot>

            <div class="space-y-4">
                <div class="w-full h-4 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex shadow-inner">
                    <div class="h-full bg-success-500 rounded-l-full transition-all duration-1000 ease-out flex items-center justify-center" style="width: {{ $workingPct }}%">
                        @if($workingPct > 10)
                            <span class="text-[10px] font-bold text-white leading-none">{{ $workingPct }}%</span>
                        @endif
                    </div>
                    <div class="h-full bg-warning-500 transition-all duration-1000 ease-out flex items-center justify-center" style="width: {{ $partialPct }}%">
                        @if($partialPct > 10)
                            <span class="text-[10px] font-bold text-white leading-none">{{ $partialPct }}%</span>
                        @endif
                    </div>
                    @if($missingPct > 0)
                        <div class="h-full bg-danger-500 rounded-r-full transition-all duration-1000 ease-out flex items-center justify-center" style="width: {{ $missingPct }}%">
                            @if($missingPct > 10)
                                <span class="text-[10px] font-bold text-white leading-none">{{ $missingPct }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="flex items-center justify-center gap-6 text-sm">
                    <span class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-success-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">Working <strong class="text-gray-900 dark:text-white">{{ $working }}</strong></span>
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-warning-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">Partial <strong class="text-gray-900 dark:text-white">{{ $partial }}</strong></span>
                    </span>
                    @if($missing > 0)
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-danger-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">Missing <strong class="text-gray-900 dark:text-white">{{ $missing }}</strong></span>
                        </span>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- ─── Filter Pills ─── --}}
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400 hidden sm:inline">Filter:</span>
            <div class="flex items-center gap-2 flex-wrap">
                <button
                    type="button"
                    class="fi-btn relative inline-flex items-center justify-center font-medium outline-none transition-all duration-75 text-sm rounded-lg px-4 py-2"
                    :class="statusFilter === 'all' ? 'bg-primary-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    x-on:click="statusFilter = 'all'"
                >
                    All
                    <span class="ml-1.5 opacity-70 text-xs">({{ $total }})</span>
                </button>
                <button
                    type="button"
                    class="fi-btn relative inline-flex items-center justify-center font-medium outline-none transition-all duration-75 text-sm rounded-lg px-4 py-2"
                    :class="statusFilter === 'working' ? 'bg-success-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    x-on:click="statusFilter = 'working'"
                >
                    Working
                    <span class="ml-1.5 opacity-70 text-xs">({{ $working }})</span>
                </button>
                <button
                    type="button"
                    class="fi-btn relative inline-flex items-center justify-center font-medium outline-none transition-all duration-75 text-sm rounded-lg px-4 py-2"
                    :class="statusFilter === 'partial' ? 'bg-warning-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    x-on:click="statusFilter = 'partial'"
                >
                    Partial
                    <span class="ml-1.5 opacity-70 text-xs">({{ $partial }})</span>
                </button>
                @if($missing > 0)
                <button
                    type="button"
                    class="fi-btn relative inline-flex items-center justify-center font-medium outline-none transition-all duration-75 text-sm rounded-lg px-4 py-2"
                    :class="statusFilter === 'missing' ? 'bg-danger-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    x-on:click="statusFilter = 'missing'"
                >
                    Missing
                    <span class="ml-1.5 opacity-70 text-xs">({{ $missing }})</span>
                </button>
                @endif
            </div>
        </div>

        {{-- ─── Section Cards ─── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            @foreach ($guideData as $section)
                @php
                    $sectionItems = collect($section['items']);
                    $sectionWorking = $sectionItems->where('status', 'working')->count();
                    $sectionPartial = $sectionItems->where('status', 'partial')->count();
                    $sectionMissing = $sectionItems->where('status', 'missing')->count();
                    $sectionTotal = $sectionItems->count();
                    $sectionWorkingPct = $sectionTotal > 0 ? round($sectionWorking / $sectionTotal * 100) : 0;
                    $sectionPartialPct = $sectionTotal > 0 ? round($sectionPartial / $sectionTotal * 100) : 0;
                    $sectionMissingPct = $sectionTotal > 0 ? round($sectionMissing / $sectionTotal * 100) : 0;
                @endphp

                <div
                    x-show="statusFilter === 'all' || (statusFilter === 'working' && {{ $sectionWorking > 0 ? 'true' : 'false' }}) || (statusFilter === 'partial' && {{ $sectionPartial > 0 ? 'true' : 'false' }}) || (statusFilter === 'missing' && {{ $sectionMissing > 0 ? 'true' : 'false' }})"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <x-filament::section class="!rounded-xl">
                        <x-slot name="heading">
                            <div class="flex items-center gap-2.5">
                                <x-dynamic-component :component="$section['icon']" class="w-5 h-5 text-primary-500 flex-shrink-0" />
                                <span class="font-semibold text-base">{{ $section['section'] }}</span>
                            </div>
                        </x-slot>
                        <x-slot name="description">
                            {{ $section['description'] }}
                        </x-slot>

                        {{-- Section badges inline --}}
                        <div class="flex items-center gap-2 flex-wrap mb-4">
                            <x-filament::badge color="success" size="sm">{{ $sectionWorking }} connected</x-filament::badge>
                            @if($sectionPartial > 0)
                                <x-filament::badge color="warning" size="sm">{{ $sectionPartial }} partial</x-filament::badge>
                            @endif
                            @if($sectionMissing > 0)
                                <x-filament::badge color="danger" size="sm">{{ $sectionMissing }} missing</x-filament::badge>
                            @endif
                        </div>

                        {{-- Section mini progress bar --}}
                        <div class="mb-4">
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
                                <div class="h-full bg-success-500 rounded-l-full transition-all" style="width: {{ $sectionWorkingPct }}%"></div>
                                @if($sectionPartial > 0)
                                    <div class="h-full bg-warning-500 transition-all" style="width: {{ $sectionPartialPct }}%"></div>
                                @endif
                                @if($sectionMissing > 0)
                                    <div class="h-full bg-danger-500 rounded-r-full transition-all" style="width: {{ $sectionMissingPct }}%"></div>
                                @endif
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="divide-y divide-gray-100 dark:divide-white/5 -mx-6 -mb-6">
                            @foreach ($section['items'] as $item)
                                @php
                                    $hasUrl = !empty($item['url']);
                                    $tag = $hasUrl ? 'a' : 'div';
                                    $hrefAttr = $hasUrl ? 'href="' . $item['url'] . '"' : '';
                                @endphp
                                <{{ $tag }}
                                    {!! $hrefAttr !!}
                                    x-show="statusFilter === 'all' || '{{ $item['status'] }}' === statusFilter"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    class="px-6 py-3 flex items-center gap-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group {{ $hasUrl ? 'cursor-pointer' : '' }}"
                                >
                                    <div class="flex-shrink-0">
                                        @if($item['status'] === 'working')
                                            <div class="w-5 h-5 rounded-full bg-success-100 dark:bg-success-500/20 flex items-center justify-center">
                                                <x-heroicon-o-check class="w-3 h-3 text-success-600 dark:text-success-400" />
                                            </div>
                                        @elseif($item['status'] === 'partial')
                                            <div class="w-5 h-5 rounded-full bg-warning-100 dark:bg-warning-500/20 flex items-center justify-center">
                                                <x-heroicon-o-minus class="w-3 h-3 text-warning-600 dark:text-warning-400" />
                                            </div>
                                        @else
                                            <div class="w-5 h-5 rounded-full bg-danger-100 dark:bg-danger-500/20 flex items-center justify-center">
                                                <x-heroicon-o-x-circle class="w-3 h-3 text-danger-600 dark:text-danger-400" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate">
                                            {{ $item['task'] }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono truncate">
                                            {{ $item['where'] }}
                                        </p>
                                    </div>

                                    <x-filament::badge
                                        :color="match($item['status']) { 'working' => 'success', 'partial' => 'warning', 'missing' => 'danger', default => 'gray' }"
                                        size="sm"
                                    >
                                        {{ ucfirst($item['status']) }}
                                    </x-filament::badge>
                                </{{ $tag }}>
                            @endforeach
                        </div>
                    </x-filament::section>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>