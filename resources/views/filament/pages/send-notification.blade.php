<x-filament-panels::page>
    <x-filament-panels::form wire:submit="sendNotification">
        {{ $this->form }}
        <div class="mt-4 mb-8">
            <x-filament::button 
                type="submit" 
                size="lg" 
                icon="heroicon-o-paper-airplane"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>
                    Dispatch Notification
                </span>
                <span wire:loading>
                    Sending...
                </span>
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    {{-- Render the History Table --}}
    <h2 class="text-xl font-bold mb-4">Notification History</h2>
    {{ $this->table }}

    @push('scripts')
    <script>
        document.addEventListener('livewire:navigated', () => {
            // Listen for notification send success
            Livewire.on('success', (data) => {
                // Browser notification
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Notification Sent!', {
                        body: 'Your notification has been successfully dispatched.',
                        icon: '/images/notification-icon.png',
                        tag: 'notification-sent'
                    });
                }
            });

            // Request notification permission on page load
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        });
    </script>
    @endpush
</x-filament-panels::page>