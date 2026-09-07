<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Admin Notifications Channels -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-md font-bold text-gray-900 dark:text-white">Admin Alert Channels</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">System channels that dispatch real-time alerts to site administrators.</p>
            </div>
            
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
                        <th class="px-6 py-4">Channel Name</th>
                        <th class="px-6 py-4">Provider</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-900 dark:divide-gray-800 dark:text-white">
                    @forelse($adminChannels as $channel)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-6 py-4 font-semibold">{{ $channel['name'] }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $channel['provider'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button 
                                    type="button" 
                                    wire:click="toggleAdminChannel({{ $channel['id'] }})"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900 {{ $channel['is_active'] ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $channel['is_active'] ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button 
                                    type="button" 
                                    wire:click="deleteAdminChannel({{ $channel['id'] }})"
                                    class="text-xs font-semibold text-danger-600 hover:text-danger-500 dark:text-danger-400 dark:hover:text-danger-300"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No admin notification channels configured.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Candidate Notifications -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                <div>
                    <h3 class="text-md font-bold text-gray-900 dark:text-white">Candidate Notifications</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Control transactional notifications and SMS templates dispatched to candidates.</p>
                </div>

                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 border-b pb-2">Candidate Email Notifications</h4>
                    
                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Completed</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send mail to Candidate when payment is completed.</p>
                        </div>
                        <input type="checkbox" wire:model="candidateSettings.email_completed" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Pending</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Inform Candidate when payment is pending.</p>
                        </div>
                        <input type="checkbox" wire:model="candidateSettings.email_pending" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Invoice Paid</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send mail to customer when the invoice is fully paid.</p>
                        </div>
                        <input type="checkbox" wire:model="candidateSettings.email_paid" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 border-b pb-2 pt-4">Candidate SMS Notifications</h4>
                    
                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Completed</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send sms to customer when payment is completed.</p>
                        </div>
                        <input type="checkbox" wire:model="candidateSettings.sms_completed" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Pending</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Inform customer when payment is pending.</p>
                        </div>
                        <input type="checkbox" wire:model="candidateSettings.sms_pending" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Invoice Paid</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send sms to customer when the invoice is fully paid.</p>
                        </div>
                        <input type="checkbox" wire:model="candidateSettings.sms_paid" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>
                </div>
            </div>

            <!-- Employer Notifications -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                <div>
                    <h3 class="text-md font-bold text-gray-900 dark:text-white">Employer Notifications</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Control transactional notifications and SMS templates dispatched to employers/recruiters.</p>
                </div>

                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 border-b pb-2">Employer Email Notifications</h4>
                    
                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Completed</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send mail to Recruiter when subscription payment is completed.</p>
                        </div>
                        <input type="checkbox" wire:model="employerSettings.email_completed" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Pending</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Inform Recruiter when subscription payment is pending.</p>
                        </div>
                        <input type="checkbox" wire:model="employerSettings.email_pending" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Invoice Paid</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send mail to employer when the invoice is fully paid.</p>
                        </div>
                        <input type="checkbox" wire:model="employerSettings.email_paid" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 border-b pb-2 pt-4">Employer SMS Notifications</h4>
                    
                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Completed</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send sms to employer when payment is completed.</p>
                        </div>
                        <input type="checkbox" wire:model="employerSettings.sms_completed" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Payment Pending</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Inform employer when payment is pending.</p>
                        </div>
                        <input type="checkbox" wire:model="employerSettings.sms_pending" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>

                    <div class="flex items-start justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900 dark:text-white">Invoice Paid</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Send sms to employer when the invoice is fully paid.</p>
                        </div>
                        <input type="checkbox" wire:model="employerSettings.sms_paid" class="rounded text-primary-600 focus:ring-primary-600 border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button 
                type="button" 
                wire:click="saveSettings" 
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            >
                @svg('heroicon-o-check', 'w-4 h-4')
                Save Changes
            </button>
        </div>
    </div>
</x-filament-panels::page>
