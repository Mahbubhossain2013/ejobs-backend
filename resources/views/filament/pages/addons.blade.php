<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Search and Info Header -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">System Addons & Custom Plugins</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Expand and upgrade your platform functionality with premium modular additions.</p>
            
            <div class="mt-4 flex flex-col sm:flex-row gap-4">
                <input 
                    type="text" 
                    wire:model.live="search" 
                    placeholder="Search addons..." 
                    class="w-full max-w-md rounded-lg border-gray-300 bg-gray-50 px-4 py-2 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>
        </div>

        <!-- Addons Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($this->getMockAddons() as $addon)
                <div class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold {{ $addon['is_active'] ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400' }}">
                                {{ $addon['status'] }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $addon['version'] }}</span>
                        </div>
                        <div>
                            <h3 class="text-md font-bold text-gray-900 dark:text-white">{{ $addon['name'] }}</h3>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ $addon['description'] }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 dark:text-gray-500">By {{ $addon['author'] }}</span>
                        @if($addon['is_active'])
                            <button disabled class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                                Installed
                            </button>
                        @else
                            <button class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-primary-500">
                                Purchase Addon
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-gray-500 dark:text-gray-400">
                    No addons found matching your search.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
