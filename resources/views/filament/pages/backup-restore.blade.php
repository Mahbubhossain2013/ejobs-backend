<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-arrow-down-tray', 'w-5 h-5 text-gray-400')
                    Create Backup
                </div>
            </x-slot>
            <x-slot name="description">
                Generate downloadable backups of your database, storage files, or the complete system.
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="p-5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            @svg('heroicon-o-circle-stack', 'w-5 h-5 text-blue-600 dark:text-blue-400')
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Database Backup</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">SQL dump of all tables</p>
                        </div>
                    </div>
                    <x-filament::button
                        wire:click="databaseBackup"
                        icon="heroicon-o-arrow-down-tray"
                        color="primary"
                        size="sm"
                        class="w-full"
                    >
                        Download SQL Dump
                    </x-filament::button>
                </div>

                <div class="p-5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            @svg('heroicon-o-folder', 'w-5 h-5 text-emerald-600 dark:text-emerald-400')
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Storage Backup</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ZIP archive of storage/app</p>
                        </div>
                    </div>
                    <x-filament::button
                        wire:click="storageBackup"
                        icon="heroicon-o-arrow-down-tray"
                        color="success"
                        size="sm"
                        class="w-full"
                    >
                        Download Storage ZIP
                    </x-filament::button>
                </div>

                <div class="p-5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            @svg('heroicon-o-archive-box', 'w-5 h-5 text-purple-600 dark:text-purple-400')
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Full System Backup</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Complete backup with DB & files</p>
                        </div>
                    </div>
                    <x-filament::button
                        wire:click="fullSystemBackup"
                        icon="heroicon-o-arrow-down-tray"
                        color="warning"
                        size="sm"
                        class="w-full"
                    >
                        Download Full Backup
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <form wire:submit="restore">
                {{ $this->form }}

                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-white/10">
                    <x-filament::button
                        type="submit"
                        size="md"
                        color="danger"
                        icon="heroicon-o-arrow-up-circle"
                        x-data
                        x-on:click="
                            if (!confirm('Are you sure you want to restore from this backup? This may overwrite existing data.')) {
                                event.preventDefault();
                            }
                        "
                    >
                        Restore from Backup
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-clock', 'w-5 h-5 text-gray-400')
                    Backup & Restore Logs
                </div>
            </x-slot>
            <x-slot name="description">
                Recent backup and restore operations with timestamps and operator details.
            </x-slot>

            <div class="mt-4">
                @if(count($backupLogs) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Date</th>
                                    <th class="px-4 py-3 font-semibold">Type</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                    <th class="px-4 py-3 font-semibold">Notes</th>
                                    <th class="px-4 py-3 font-semibold">IP Address</th>
                                    <th class="px-4 py-3 font-semibold">Performed By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach($backupLogs as $log)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-mono text-xs">
                                            {{ \Carbon\Carbon::parse($log['created_at'])->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                {{ $log['to_version'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(($log['status'] ?? '') === 'success')
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Success</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">Failed</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs max-w-[300px] truncate">
                                            {{ $log['notes'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">
                                            {{ $log['ip_address'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-xs">
                                            {{ $log['performed_by'] ?? 'System' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                        <p class="text-sm">No backup or restore logs found.</p>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
