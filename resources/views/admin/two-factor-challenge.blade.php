<x-filament-panels::page.simple>
    <form wire:submit="verify" class="space-y-6 mt-6">
        @if ($errors->any())
            <div class="p-4 rounded-xl bg-danger-500/10 border border-danger-500/20 text-danger-600 dark:text-danger-400 text-sm">
                @foreach ($errors->all() as $error)
                    <p>⚠️ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="space-y-2">
            <x-filament::input.wrapper :valid="!$errors->has('code')">
                <x-filament::input
                    type="text"
                    wire:model="code"
                    id="code"
                    required
                    autofocus
                    placeholder="e.g. 123456"
                    class="text-center font-mono tracking-widest text-lg"
                />
            </x-filament::input.wrapper>
        </div>

        <x-filament::button type="submit" color="primary" class="w-full" wire:loading.attr="disabled" wire:target="verify">
            <span wire:loading.remove wire:target="verify">Confirm Session</span>
            <span wire:loading wire:target="verify">Verifying Session...</span>
        </x-filament::button>

        <div class="text-center mt-4">
            <a href="/admin/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:underline">
                Cancel & Logout
            </a>
        </div>
    </form>

    <form id="logout-form" action="/admin/logout" method="POST" class="hidden">
        @csrf
    </form>
</x-filament-panels::page.simple>
