<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-eye', 'w-5 h-5 text-gray-400')
                    Footer Preview
                </div>
            </x-slot>

            <div class="mt-4 p-6 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                <div class="text-center space-y-2">
                    @php
                        $copyrightText = $this->data['footer_copyright_text'] ?? '';
                        $copyrightLine = $copyrightText
                            ? $copyrightText
                            : '&copy; ' . date('Y') . ' ' . ($this->data['site_name'] ?? config('app.name', 'Job Portal')) . '. All rights reserved.';
                    @endphp
                    <p class="text-sm text-gray-500 dark:text-gray-400">{!! $copyrightLine !!}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        <a href="https://nextin.fobign.com" target="_blank" class="underline hover:text-primary-500 transition-colors">
                            Developed by NEXTIN
                        </a>
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-filament-panels::form wire:submit="save">
                {{ $this->form }}

                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-white/10">
                    <x-filament::button type="submit" size="md" color="primary" icon="heroicon-o-check">
                        Save Footer Settings
                    </x-filament::button>
                </div>
            </x-filament-panels::form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
