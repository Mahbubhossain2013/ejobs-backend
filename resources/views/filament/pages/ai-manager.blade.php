<x-filament-panels::page>
    {{-- Show recommendation banner only when Fobign is NOT enabled --}}
    @unless($fobignEnabled)
        <div class="mb-6 p-4 rounded-xl bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800">
            <div class="flex items-start gap-3">
                <div class="shrink-0 mt-0.5">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h3 class="font-semibold text-primary-900 dark:text-primary-100">
                        Recommended: Fobign FIN
                    </h3>
                    <p class="text-sm text-primary-700 dark:text-primary-300 mt-1">
                        <strong>Fobign FIN</strong> is the recommended AI provider for this platform — optimized for job portal workflows including CV analysis, job matching, and career coaching. Set as <strong>Priority 1</strong> for best performance.
                    </p>
                    @if(!empty($fobignModels))
                        <p class="text-xs text-primary-600 dark:text-primary-400 mt-1">
                            Supported models: {{ implode(', ', $fobignModels) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endunless

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" size="lg" color="success">
                Apply & Deploy AI Algorithm Configs
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
