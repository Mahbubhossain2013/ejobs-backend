<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Status Card using Native Filament Section -->
        <x-filament::section>
            <x-slot name="heading">
                Cron Job Status
            </x-slot>
            <x-slot name="description">
                Monitor scheduled queue worker heartbeats and task health.
            </x-slot>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Last Cron Run: <span class="text-primary-500 font-mono">{{ $lastCronRun }}</span>
                    </span>
                </div>
                <div>
                    <x-filament::button 
                        type="button" 
                        wire:click="runCronManually" 
                        icon="heroicon-o-arrow-path"
                        color="primary"
                        size="md"
                    >
                        Run Cron Manually
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <!-- Command Cards using Native Filament Section -->
        <x-filament::section>
            <x-slot name="heading">
                Cron Job Instructions
            </x-slot>
            <x-slot name="description">
                Add this cron job in your hosting control panel to run every minute
            </x-slot>

            <div class="space-y-6 mt-6">
                <!-- Web Cron via cURL -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                        @svg('heroicon-o-globe-alt', 'w-4 h-4 text-gray-400')
                        Web Cron via cURL
                    </label>
                    <x-filament::input.wrapper icon="heroicon-o-link">
                        <x-filament::input 
                            type="text" 
                            readonly 
                            value="{{ $webCronUrl }}" 
                            onclick="this.select()"
                            class="font-mono text-xs cursor-pointer focus:ring-0"
                        />
                    </x-filament::input.wrapper>
                </div>

                <!-- Full Path Command -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                        @svg('heroicon-o-command-line', 'w-4 h-4 text-gray-400')
                        Full Path Command
                    </label>
                    <x-filament::input.wrapper icon="heroicon-o-code-bracket">
                        <x-filament::input 
                            type="text" 
                            readonly 
                            value="{{ $fullPathCommand }}" 
                            onclick="this.select()"
                            class="font-mono text-xs cursor-pointer focus:ring-0"
                        />
                    </x-filament::input.wrapper>
                </div>

                <!-- Warning/Alert Block -->
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/40 dark:bg-amber-950/10">
                    <div class="flex gap-3">
                        @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0')
                        <div>
                            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                Compatibility Warning
                            </p>
                            <p class="mt-1 text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                                Ensure the PHP binary supports PHP 8.2+ with IonCube Loader.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>