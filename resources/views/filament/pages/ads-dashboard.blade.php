<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Header --}}
        <div>
            @livewire(\App\Filament\Widgets\PromotionStatsOverview::class)
        </div>

        {{-- Main Analytics Charts Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                @livewire(\App\Filament\Widgets\PromotionRevenueChart::class)
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                @livewire(\App\Filament\Widgets\PromotionPerformanceChart::class)
            </div>
        </div>

        {{-- Bottom Charts & Admin Guidelines --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                @livewire(\App\Filament\Widgets\TopPromotionsChart::class)
            </div>

            <div class="bg-gradient-to-br from-indigo-900 to-slate-900 dark:from-gray-900 dark:to-slate-950 text-white p-6 rounded-2xl shadow-lg border border-indigo-950 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <span class="p-2 rounded-lg bg-indigo-500/20 text-indigo-300">
                            <x-heroicon-o-shield-check class="h-6 w-6" />
                        </span>
                        <h4 class="text-lg font-bold tracking-tight">System Safety Status</h4>
                    </div>
                    <p class="text-sm text-indigo-200/90 leading-relaxed mb-4">
                        Revenue protection systems are fully active. All campaigns must pass safety checks unless whitelisted.
                    </p>
                    <ul class="text-xs text-indigo-300 space-y-2">
                        <li class="flex items-center gap-2">
                            <x-heroicon-m-check-circle class="h-4 w-4 text-emerald-400" />
                            AI Safety Queue checking active (100%)
                        </li>
                        <li class="flex items-center gap-2">
                            <x-heroicon-m-check-circle class="h-4 w-4 text-emerald-400" />
                            Hourly Billing deduction cron active
                        </li>
                        <li class="flex items-center gap-2">
                            <x-heroicon-m-check-circle class="h-4 w-4 text-emerald-400" />
                            Click Fraud telemetry filters enabled
                        </li>
                    </ul>
                </div>

                <div class="pt-6 border-t border-indigo-800/60 mt-6 flex justify-between items-center text-xs text-indigo-300/80">
                    <span>Algorithm: v2.4.2-auto</span>
                    <a href="{{ url('/admin/marketing/ads') }}" class="font-semibold text-white hover:underline flex items-center gap-1">
                        Campaign Manager <x-heroicon-m-arrow-right class="h-3 w-3" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
