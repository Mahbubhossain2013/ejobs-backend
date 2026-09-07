<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-tag', 'w-5 h-5 text-gray-400')
                    Version Information
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                <div class="flex flex-col items-center p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Current Version</span>
                    <span class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-2">{{ $currentVersion }}</span>
                </div>
                <div class="flex flex-col items-center p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Build Number</span>
                    <span class="text-2xl font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $buildNumber }}</span>
                </div>
                <div class="flex flex-col items-center p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/10">
                    <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Release Date</span>
                    <span class="text-lg font-bold text-gray-800 dark:text-gray-200 mt-2">{{ $releaseDate }}</span>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-code-bracket', 'w-5 h-5 text-gray-400')
                    Runtime Environment
                </div>
            </x-slot>

            <div class="mt-4 divide-y divide-gray-100 dark:divide-white/10">
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-globe-alt', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Environment</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                        @if($environment === 'production') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                        @elseif($environment === 'local') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                        @else bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                        @endif">
                        {{ ucfirst($environment) }}
                    </span>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-server-stack', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">PHP Version</span>
                    </div>
                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $phpVersion }}</span>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-bolt', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Laravel Version</span>
                    </div>
                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $laravelVersion }}</span>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-paint-brush', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filament Version</span>
                    </div>
                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $filamentVersion }}</span>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-code-bracket', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">React Version</span>
                    </div>
                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $reactVersion }}</span>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-cpu-chip', 'w-5 h-5 text-gray-400')
                    Server & Resources
                </div>
            </x-slot>

            <div class="mt-4 divide-y divide-gray-100 dark:divide-white/10">
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-squares-2x2', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Server Software</span>
                    </div>
                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $serverSoftware }}</span>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-circle-stack', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">MySQL Version</span>
                    </div>
                    <span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $mysqlVersion }}</span>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-bolt', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Redis Status</span>
                    </div>
                    @if($redisAvailable)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Online
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Offline
                        </span>
                    @endif
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-server-stack', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Disk Usage</span>
                    </div>
                    <span class="text-sm font-mono font-semibold text-primary-600 dark:text-primary-400">{{ $diskUsage }}</span>
                </div>

                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        @svg('heroicon-o-device-phone-mobile', 'w-4 h-4 text-gray-400')
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memory Usage</span>
                    </div>
                    <span class="text-sm font-mono font-semibold text-primary-600 dark:text-primary-400">{{ $memoryUsage }}</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
