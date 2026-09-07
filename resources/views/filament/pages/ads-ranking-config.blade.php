<x-filament-panels::page>
    <x-filament-panels::form wire:submit="saveSettings">
        {{ $this->form }}

        <div class="flex justify-end gap-3">
            <x-filament::button type="submit" color="primary">
                Save Algorithm Config
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    {{-- AI Recommendations Panel using Filament Components --}}
    <x-filament::section class="mt-8">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <span class="p-1 rounded-lg bg-indigo-500/10 text-indigo-500">
                    <x-heroicon-o-cpu-chip class="h-6 w-6" />
                </span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">AI Algorithmic Recommendations</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Platforms automated intelligence suggestions to maximize CTR conversion rates and balance category supplies.
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
            {{-- Suggestion 1 --}}
            <div class="p-5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 flex flex-col justify-between">
                <div>
                    <h5 class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">Optimization: Increase backend job ads visibility by 12%</h5>
                    <p class="text-xs text-indigo-850 dark:text-indigo-200 mt-2 leading-relaxed">
                        Identified backend skills undersupply across candidate listings. Increases Relevance weight contribution from 40% to 52% while reducing raw bids impact to protect user experience.
                    </p>
                </div>
                <div class="mt-5">
                    <x-filament::button wire:click="applySuggestion(52, 20, 18, 10, 'Increase backend job ads visibility')" size="xs" color="indigo" class="w-full">
                        Apply Suggested Ratios
                    </x-filament::button>
                </div>
            </div>

            {{-- Suggestion 2 --}}
            <div class="p-5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 flex flex-col justify-between">
                <div>
                    <h5 class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wide">Optimization: Reduce low CTR campaigns ranking weight</h5>
                    <p class="text-xs text-indigo-850 dark:text-indigo-200 mt-2 leading-relaxed">
                        Flags low-CTR ads with high impression values but weak conversions feedback. Reduces CTR weight from 20% to 10% and boosts bid size weights to penalize poor creatives.
                    </p>
                </div>
                <div class="mt-5">
                    <x-filament::button wire:click="applySuggestion(40, 40, 10, 10, 'Reduce low CTR campaigns weight')" size="xs" color="indigo" class="w-full">
                        Apply Suggested Ratios
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
