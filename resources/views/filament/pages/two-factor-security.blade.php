<x-filament-panels::page>
    {{ $this->form }}

    <div class="space-y-6 mt-6">
        <x-filament::section>
            <x-slot name="heading">Trusted Devices</x-slot>
            <x-slot name="subheading">Manage the browsers and devices you have trusted to bypass MFA challenge verification.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Security Logs</x-slot>
            <x-slot name="subheading">Audit trails of all profile-related authentication activities and system overrides.</x-slot>
            
            {{ $this->securityLogsTable }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
