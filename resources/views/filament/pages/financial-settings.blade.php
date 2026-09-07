<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        <x-filament-actions::actions :actions="[\Filament\Actions\Action::make('save')->label('Save Changes')->submit('save')]" />
    </x-filament-panels::form>
</x-filament-panels::page>
