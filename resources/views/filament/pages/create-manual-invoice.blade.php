<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Create Manual Invoice</h2>

            <x-filament-panels::form wire:submit="create">
                {{ $this->form }}

                <div class="mt-6 flex gap-3">
                    @foreach($this->getFormActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page>
