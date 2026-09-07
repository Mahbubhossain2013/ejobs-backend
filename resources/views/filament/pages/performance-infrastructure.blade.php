<x-filament-panels::page>
    <div class="space-y-6">
        
        <!-- Live Diagnostic Gauges Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <!-- Redis Status -->
            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-cpu-chip', 'w-5 h-5 text-gray-400')
                        Redis Cache
                    </div>
                </x-slot>
                <div class="space-y-4 mt-2">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3.5 w-3.5">
                            @if($redisStatus['available'])
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $redisStatus['available'] ? 'Online (Active)' : 'Offline (Disabled)' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1 font-mono">
                        <p>Host: {{ $redisStatus['host'] }}:{{ $redisStatus['port'] }}</p>
                        @if($redisStatus['available'])
                            <p class="text-primary-500">Latency: {{ $redisStatus['latency_ms'] }}ms</p>
                        @else
                            <p class="text-rose-500 text-[10px]">{{ $redisStatus['error'] ?? 'Connection Refused' }}</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>

            <!-- Queue Status -->
            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-clock', 'w-5 h-5 text-gray-400')
                        Queue Worker
                    </div>
                </x-slot>
                <div class="space-y-4 mt-2">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3.5 w-3.5">
                            @if($queueDriver === 'redis')
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200 capitalize">
                            Driver: {{ $queueDriver }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1 font-mono">
                        <p>Pending Jobs: <span class="font-bold text-primary-500">{{ $queueSize }}</span></p>
                        <p class="text-[10px]">Auto-failover connection</p>
                    </div>
                </div>
            </x-filament::section>

            <!-- Search Status -->
            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-magnifying-glass', 'w-5 h-5 text-gray-400')
                        Search (Scout)
                    </div>
                </x-slot>
                <div class="space-y-4 mt-2">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3.5 w-3.5">
                            @if($meilisearchStatus)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $meilisearchStatus ? 'Meilisearch (Online)' : 'Standard DB (Fallback)' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1 font-mono">
                        <p>Host: {{ env('MEILISEARCH_HOST', '127.0.0.1:7700') }}</p>
                        <p class="text-[10px]">Zero-downtime SQL fallbacks</p>
                    </div>
                </div>
            </x-filament::section>

            <!-- Realtime Status -->
            <x-filament::section class="h-full">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-signal', 'w-5 h-5 text-gray-400')
                        WebSockets
                    </div>
                </x-slot>
                <div class="space-y-4 mt-2">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-3.5 w-3.5">
                            @if($websocketStatus)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500"></span>
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $websocketStatus ? 'Reverb (Active)' : 'AJAX Polling (Fallback)' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 space-y-1 font-mono">
                        <p>Reverb Port: 8080</p>
                        <p class="text-[10px]">Resilient front-end updates</p>
                    </div>
                </div>
            </x-filament::section>

        </div>

        <!-- Infrastructure Administrative Actions -->
        <x-filament::section>
            <x-slot name="heading">
                Performance & Cache Management Actions
            </x-slot>
            <x-slot name="description">
                Direct infrastructure commands to maintain platform speeds and clear stale caches.
            </x-slot>
            
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
                <!-- Clear Cache -->
                <x-filament::button 
                    wire:click="clearCache" 
                    icon="heroicon-o-trash"
                    color="primary"
                    size="md"
                >
                    Clear Cache
                </x-filament::button>

                <!-- Restart Queues -->
                <x-filament::button 
                    wire:click="restartQueues" 
                    icon="heroicon-o-arrow-path"
                    color="warning"
                    size="md"
                >
                    Restart Queue
                </x-filament::button>

                <!-- Clear Sessions -->
                <x-filament::button 
                    wire:click="clearSessions" 
                    icon="heroicon-o-users"
                    color="danger"
                    size="md"
                >
                    Clear Sessions
                </x-filament::button>

                <!-- Flush Redis -->
                <x-filament::button 
                    wire:click="flushRedis" 
                    icon="heroicon-o-fire"
                    color="danger"
                    size="md"
                    :disabled="!$redisStatus['available']"
                >
                    Flush Redis
                </x-filament::button>

                <!-- Rebuild Search Index -->
                <x-filament::button 
                    wire:click="rebuildSearchIndex" 
                    icon="heroicon-o-magnifying-glass"
                    color="success"
                    size="md"
                    :disabled="!$meilisearchStatus"
                >
                    Rebuild Index
                </x-filament::button>
            </div>
        </x-filament::section>

        <!-- Dynamic Fallback Priority & Secondary Configurations -->
        <x-filament::section>
            <x-slot name="heading">
                Priority Fallback Settings
            </x-slot>
            <x-slot name="description">
                Customize recovery triggers and backup email settings for zero-downtime execution.
            </x-slot>

            <form wire:submit.prevent="saveSettings" class="space-y-6 mt-6">
                <!-- Redis override -->
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Disable Redis Dynamically</h4>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                            Forcibly downgrade Cache, Sessions, and Queues to MySQL Database driver even if Redis is listening.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="redisForceDisabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-650 peer-checked:bg-primary-600"></div>
                    </label>
                </div>

                <!-- Secondary SMTP config -->
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        @svg('heroicon-o-envelope', 'w-4 h-4 text-gray-400')
                        Backup SMTP Fallover
                    </h4>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        If primary SMTP delivery encounters an error or timeouts, systems automatically redirect mail packages using these fallback credentials.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Backup SMTP Host</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="secondaryMailHost" placeholder="smtp.gmail.com" />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Backup SMTP Port</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="secondaryMailPort" placeholder="587" />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Backup Username</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" wire:model="secondaryMailUsername" placeholder="backup@domain.com" />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Backup Password</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="password" wire:model="secondaryMailPassword" placeholder="••••••••" />
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 dark:border-gray-800 pt-5">
                    <x-filament::button type="submit" size="md" color="primary">
                        Save Configurations
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
