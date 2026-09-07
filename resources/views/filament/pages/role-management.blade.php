<x-filament-panels::page>
    <form wire:submit="save">
        {!! $this->form->render() !!}

        <div class="mt-6 flex gap-3">
            <x-filament::button type="submit">
                Save Role
            </x-filament::button>

            @if(!empty($formData['existing_role']) && $formData['existing_role'] !== 'super_admin')
                <x-filament::button color="danger" wire:click="delete">
                    Delete Role
                </x-filament::button>
            @endif
        </div>
    </form>

    @php
        $roleUsers = $this->getRoleUsers();
        $availableRoles = $this->getAvailableRoles();
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            Assigned Users by Role
        </x-slot>
        <x-slot name="description">
            Manage admin panel access for each user. Change roles or remove access.
        </x-slot>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mt-4">
            @foreach(['super_admin' => 'Super Admin', 'admin' => 'Admin', 'manager' => 'Manager'] as $role => $label)
                <x-filament::section>
                    <div class="flex items-center gap-2 mb-3">
                        @if($role === 'super_admin')
                            <x-heroicon-s-shield-check class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                        @elseif($role === 'admin')
                            <x-heroicon-s-user class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                        @else
                            <x-heroicon-s-briefcase class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                        @endif
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $label }}</h3>
                        <x-filament::badge color="gray">{{ $roleUsers[$role]->count() }}</x-filament::badge>
                    </div>

                    @if($roleUsers[$role]->isEmpty())
                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">No users assigned</p>
                    @else
                        <div class="space-y-3">
                            @foreach($roleUsers[$role] as $user)
                                <div class="rounded-lg border border-gray-200 dark:border-white/10 p-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                                        </div>
                                    </div>

                                    @if($role !== 'super_admin' && $user->id !== auth()->id())
                                        <div class="mt-2 flex items-center gap-2">
                                            <select
                                                wire:change="changeUserRole({{ $user->id }}, $event.target.value)"
                                                class="text-xs rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-700 dark:text-gray-300 px-2 py-1 focus:ring-1 focus:ring-primary-500"
                                            >
                                                @foreach($availableRoles as $r)
                                                    <option value="{{ $r }}" {{ $r === $role ? 'selected' : '' }}>
                                                        {{ str($r)->replace('_', ' ')->title() }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button
                                                type="button"
                                                wire:click="removeFromPanel({{ $user->id }})"
                                                wire:confirm="Remove {{ $user->name }} from admin panel?"
                                                class="text-xs text-danger-600 dark:text-danger-400 hover:text-danger-700 dark:hover:text-danger-300 font-medium"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
