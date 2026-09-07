<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-currency-dollar', 'w-5 h-5 text-primary-500')
                    Currency Management
                </div>
            </x-slot>
            <x-slot name="description">
                BDT (Taka) is the default currency. All prices are displayed in BDT.
            </x-slot>

            <div class="mt-4 p-4 rounded-xl bg-primary-50 dark:bg-primary-900/10 border border-primary-200 dark:border-primary-800/30">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-primary-600 dark:text-primary-400')
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-primary-800 dark:text-primary-200">BDT (Bangladeshi Taka)</p>
                        <p class="text-xs text-primary-600 dark:text-primary-400">Default currency · Symbol: ৳ · Rate: 1.00000000</p>
                    </div>
                    <x-filament::badge color="success" size="sm" class="ml-auto">Active</x-filament::badge>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-information-circle', 'w-5 h-5 text-gray-400')
                    Future Multi-Currency Support
                </div>
            </x-slot>
            <x-slot name="description">
                Additional currencies can be added here when multi-currency is enabled.
            </x-slot>

            <div class="mt-4 divide-y divide-gray-200 dark:divide-white/5 border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden">
                @forelse($this->getCurrencies() as $currency)
                    <div class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <span class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ $currency->symbol }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $currency->code }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">1 {{ $currency->code }} = {{ number_format($currency->rate, 8) }} USD</p>
                            </div>
                            @if($currency->code === 'BDT')
                                <x-filament::badge color="success" size="sm">Default</x-filament::badge>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                wire:click="toggleCurrency({{ $currency->id }})"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900 {{ $currency->enabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                            >
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $currency->enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @if($currency->code !== 'BDT' && $currency->code !== 'USD')
                                <button
                                    type="button"
                                    wire:click="deleteCurrency({{ $currency->id }})"
                                    class="text-xs font-medium text-danger-600 hover:text-danger-500 dark:text-danger-400 dark:hover:text-danger-300"
                                >
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No currencies found.
                    </div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
