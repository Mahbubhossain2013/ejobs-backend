<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Search and Hub Layout using Native Filament Section -->
        <x-filament::section>
            <x-slot name="heading">
                System Settings Panel
            </x-slot>
            <x-slot name="description">
                Adjust and fine-tune all administrative features and preferences.
            </x-slot>
            
            <div class="mt-4">
                <x-filament::input.wrapper icon="heroicon-o-magnifying-glass">
                    <x-filament::input 
                        type="text" 
                        wire:model.live="search" 
                        placeholder="Search for general, mail, sms, cron settings..." 
                    />
                </x-filament::input.wrapper>
            </div>
        </x-filament::section>

        <!-- Dynamic Grid with Filament Native Design Alignment & Transitions -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($this->getSettingsCards() as $card)
                @if(!isset($card['visible']) || $card['visible']())
                <a href="{{ $card['url'] }}" class="group block h-full">
                    <x-filament::section class="h-full transition duration-200 ease-in-out hover:scale-[1.02] hover:shadow-md hover:border-primary-500 dark:hover:border-primary-500">
                        <div class="flex items-start gap-4">
                            <div class="rounded-xl bg-primary-50 dark:bg-primary-950/40 p-3 text-primary-600 dark:text-primary-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition">
                                @svg($card['icon'], 'w-6 h-6')
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition">
                                    {{ $card['title'] }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                    {{ $card['description'] }}
                                </p>
                            </div>
                        </div>
                    </x-filament::section>
                </a>
                @endif
            @empty
                <div class="col-span-full py-12">
                    <x-filament::section class="text-center">
                        <div class="text-gray-500 dark:text-gray-400 space-y-2">
                            <div class="flex justify-center text-gray-300 dark:text-gray-600">
                                @svg('heroicon-o-magnifying-glass', 'w-12 h-12')
                            </div>
                            <p class="text-sm font-medium">No settings pages matched your search.</p>
                        </div>
                    </x-filament::section>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>