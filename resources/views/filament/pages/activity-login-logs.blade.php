<x-filament-panels::page>
    @php $stats = $this->getStats(); @endphp

    {{-- Stats Row --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach($stats as $stat)
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div @class([
                        'flex items-center justify-center w-10 h-10 rounded-lg',
                        'bg-success-50 dark:bg-success-950' => $stat['color'] === 'success',
                        'bg-danger-50 dark:bg-danger-950' => $stat['color'] === 'danger',
                        'bg-warning-50 dark:bg-warning-950' => $stat['color'] === 'warning',
                        'bg-info-50 dark:bg-info-950' => $stat['color'] === 'info',
                        'bg-gray-50 dark:bg-gray-950' => $stat['color'] === 'gray',
                    ])>
                        <x-dynamic-component
                            :component="$stat['icon']"
                            @class([
                                'w-5 h-5',
                                'text-success-600 dark:text-success-400' => $stat['color'] === 'success',
                                'text-danger-600 dark:text-danger-400' => $stat['color'] === 'danger',
                                'text-warning-600 dark:text-warning-400' => $stat['color'] === 'warning',
                                'text-info-600 dark:text-info-400' => $stat['color'] === 'info',
                                'text-gray-600 dark:text-gray-400' => $stat['color'] === 'gray',
                            ])
                        />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>
