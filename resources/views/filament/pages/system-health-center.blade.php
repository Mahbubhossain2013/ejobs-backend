<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Real-time monitoring of all system services and dependencies.
                </p>
            </div>
            <x-filament::button
                wire:click="runHealthChecks"
                icon="heroicon-o-arrow-path"
                color="primary"
                size="sm"
            >
                Refresh All
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-server', 'w-5 h-5 text-gray-400')
                        Database Status
                    </div>
                </x-slot>
                <div class="space-y-3 mt-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            @if($dbConnected)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $dbConnected ? 'Connected' : 'Disconnected' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
                        <p>Driver: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ strtoupper($dbDriver) }}</span></p>
                        <p>Version: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $dbVersion }}</span></p>
                        @if(!$dbConnected && $dbError)
                            <p class="text-rose-500 text-[10px]">{{ Str::limit($dbError, 80) }}</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-clock', 'w-5 h-5 text-gray-400')
                        Queue Status
                    </div>
                </x-slot>
                <div class="space-y-3 mt-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            @if($queueFailed === 0)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            @elseif($queueFailed < 10)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ ucfirst($queueDriver) }} Driver
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
                        <p>Pending Jobs: <span class="font-bold text-primary-500">{{ $queuePending }}</span></p>
                        <p>Failed Jobs: <span class="font-bold {{ $queueFailed > 0 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $queueFailed }}</span></p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-bolt', 'w-5 h-5 text-gray-400')
                        Redis Status
                    </div>
                </x-slot>
                <div class="space-y-3 mt-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            @if($redisHealthy)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $redisHealthy ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
                        <p>Host: {{ $redisHost }}:{{ $redisPort }}</p>
                        @if($redisHealthy)
                            <p>Latency: <span class="text-emerald-500">{{ $redisLatency }}ms</span></p>
                        @else
                            <p class="text-rose-500 text-[10px]">{{ $redisError ?: 'Connection Refused' }}</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-envelope', 'w-5 h-5 text-gray-400')
                        Mail Status
                    </div>
                </x-slot>
                <div class="space-y-3 mt-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            @if($mailHealthy)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $mailHealthy ? 'Reachable' : 'Unreachable' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
                        <p>Driver: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ strtoupper($mailDriver) }}</span></p>
                        <p>Host: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $mailHost }}</span></p>
                        @if(!$mailHealthy && $mailError)
                            <p class="text-rose-500 text-[10px]">{{ Str::limit($mailError, 80) }}</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-calendar', 'w-5 h-5 text-gray-400')
                        Scheduler Status
                    </div>
                </x-slot>
                <div class="space-y-3 mt-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            @if($schedulerHealthy)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $schedulerStatus }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
                        <p>Last Run: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $lastCronRun }}</span></p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-folder', 'w-5 h-5 text-gray-400')
                        Storage Status
                    </div>
                </x-slot>
                <div class="space-y-3 mt-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            @if($storageWritable)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $storageWritable ? 'Writable' : 'Read-Only' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
                        <p>Driver: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ strtoupper($storageDriver) }}</span></p>
                        <p>Usage: <span class="font-bold text-primary-500">{{ $storageDiskUsage }}</span></p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-globe-alt', 'w-5 h-5 text-gray-400')
                        API Status
                    </div>
                </x-slot>
                <div class="space-y-3 mt-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            @if($apiHealthy)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $apiHealthy ? 'Sanctum Active' : 'Error' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 font-mono">
                        <p>Active Tokens: <span class="font-bold text-primary-500">{{ number_format($apiTokenCount) }}</span></p>
                        @if(!$apiHealthy && $apiError)
                            <p class="text-rose-500 text-[10px]">{{ Str::limit($apiError, 80) }}</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-wrench', 'w-5 h-5 text-gray-400')
                    Quick Actions
                </div>
            </x-slot>
            <x-slot name="description">
                Administrative actions to resolve common system issues.
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <x-filament::button
                    wire:click="clearCache"
                    icon="heroicon-o-trash"
                    color="primary"
                    size="md"
                >
                    Clear Cache
                </x-filament::button>

                <x-filament::button
                    wire:click="restartQueue"
                    icon="heroicon-o-arrow-path"
                    color="warning"
                    size="md"
                >
                    Restart Queue
                </x-filament::button>

                <x-filament::button
                    wire:click="flushRedis"
                    icon="heroicon-o-fire"
                    color="danger"
                    size="md"
                    :disabled="!$redisHealthy"
                >
                    Flush Redis
                </x-filament::button>

                <x-filament::button
                    wire:click="runScheduler"
                    icon="heroicon-o-calendar"
                    color="success"
                    size="md"
                >
                    Run Scheduler
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
