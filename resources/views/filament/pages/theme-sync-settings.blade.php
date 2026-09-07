<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-eye', 'w-5 h-5 text-gray-400')
                    Theme Preview
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="p-4 rounded-xl border border-gray-200 dark:border-white/10">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">Primary</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg border border-gray-200 dark:border-white/10" style="background-color: {{ $this->data['theme_primary_color'] ?? '#2563EB' }}"></div>
                        <span class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $this->data['theme_primary_color'] ?? '#2563EB' }}</span>
                    </div>
                </div>
                <div class="p-4 rounded-xl border border-gray-200 dark:border-white/10">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">Secondary</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg border border-gray-200 dark:border-white/10" style="background-color: {{ $this->data['theme_secondary_color'] ?? '#7C3AED' }}"></div>
                        <span class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $this->data['theme_secondary_color'] ?? '#7C3AED' }}</span>
                    </div>
                </div>
                <div class="p-4 rounded-xl border border-gray-200 dark:border-white/10">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">Accent</p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg border border-gray-200 dark:border-white/10" style="background-color: {{ $this->data['theme_accent_color'] ?? '#F59E0B' }}"></div>
                        <span class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $this->data['theme_accent_color'] ?? '#F59E0B' }}</span>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <form wire:submit="save">
                {{ $this->form }}

                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-white/10">
                    <x-filament::button type="submit" size="md" color="primary" icon="heroicon-o-check">
                        Save Theme Settings
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
