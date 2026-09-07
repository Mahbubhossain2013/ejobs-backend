<x-filament-panels::page>
    {{-- Alpine-based state management for credits/debits interactive modals --}}
    <div x-data="{ 
        modalOpen: false, 
        modalType: 'deduct', 
        walletId: null, 
        employerName: '',
        amount: '',
        reason: '',
        openModal(type, id, name) {
            this.modalType = type;
            this.walletId = id;
            this.employerName = name;
            this.amount = '';
            this.reason = '';
            this.modalOpen = true;
        },
        submitAction() {
            if (!this.amount || isNaN(this.amount) || parseFloat(this.amount) <= 0) {
                alert('Please enter a valid amount.');
                return;
            }
            if (this.modalType === 'deduct') {
                $wire.forceDeduct(this.walletId, parseFloat(this.amount), this.reason);
            } else {
                $wire.refundCampaign(this.walletId, parseFloat(this.amount), this.reason);
            }
            this.modalOpen = false;
        }
    }">
        {{-- Stats Widgets --}}
        @livewire(\App\Filament\Widgets\InvoiceStatusOverview::class)

        {{-- Revenue Chart --}}
        @livewire(\App\Filament\Widgets\InvoiceRevenueChart::class)

        {{-- Quick Revenue Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
            @foreach([
                ['label' => 'Subscriptions', 'value' => '৳'.number_format($this->stats['subscription_revenue'] ?? 0, 2), 'icon' => 'heroicon-o-credit-card', 'color' => 'blue'],
                ['label' => 'Job Boosts', 'value' => '৳'.number_format($this->stats['boost_revenue'] ?? 0, 2), 'icon' => 'heroicon-o-rocket-launch', 'color' => 'orange'],
                ['label' => 'Milestones', 'value' => '৳'.number_format($this->stats['milestone_revenue'] ?? 0, 2), 'icon' => 'heroicon-o-flag', 'color' => 'green'],
                ['label' => 'Escrow Funded', 'value' => '৳'.number_format($this->stats['escrow_funded_month'] ?? 0, 2), 'icon' => 'heroicon-o-lock-closed', 'color' => 'purple'],
            ] as $card)
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/40">
                        <x-dynamic-component :component="$card['icon']" class="h-5 w-5 text-indigo-600 dark:text-indigo-450" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['label'] }} (Month)</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Consolidated Employer Wallet Control Panel --}}
        <div class="mt-8 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/40">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Employer Wallets & System Overrides</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Control credits, freeze accounts, and adjust advertising deductions.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50/80 dark:bg-gray-900/50">
                        <tr class="text-xs text-gray-500 uppercase font-semibold">
                            <th class="px-6 py-3.5 text-left">Employer</th>
                            <th class="px-6 py-3.5 text-left">Available Balance</th>
                            <th class="px-6 py-3.5 text-left">Locked Daily Budget</th>
                            <th class="px-6 py-3.5 text-left">Wallet Privilege</th>
                            <th class="px-6 py-3.5 text-right">Administrative Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 font-medium">
                        @forelse($this->wallets as $wallet)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-gray-900 dark:text-white block font-bold">{{ $wallet->user?->name ?? '—' }}</span>
                                <span class="text-2xs text-gray-400 font-medium">{{ $wallet->user?->email }}</span>
                            </td>
                            <td class="px-6 py-4 text-emerald-600 dark:text-emerald-450 font-bold">
                                ৳{{ number_format($wallet->balance, 2) }}
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                ৳{{ number_format($wallet->locked_balance, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-3xs font-bold uppercase
                                    {{ $wallet->is_frozen ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $wallet->is_frozen ? 'FROZEN' : 'ACTIVE' }}
                                </span>
                                @if($wallet->is_frozen)
                                <span class="text-3xs text-rose-500 block font-medium mt-0.5">{{ $wallet->freeze_reason }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                {{-- Deduct Balance button --}}
                                <button @click="openModal('deduct', {{ $wallet->id }}, '{{ addslashes($wallet->user?->name) }}')"
                                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/20 dark:hover:bg-amber-950/30 text-amber-700 dark:text-amber-400 rounded-lg text-xs transition-colors">
                                    Deduct
                                </button>
                                
                                {{-- Refund Balance button --}}
                                <button @click="openModal('refund', {{ $wallet->id }}, '{{ addslashes($wallet->user?->name) }}')"
                                    class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/20 dark:hover:bg-blue-950/30 text-blue-700 dark:text-blue-400 rounded-lg text-xs transition-colors">
                                    Refund
                                </button>

                                {{-- Freeze/Unfreeze toggle --}}
                                @if($wallet->is_frozen)
                                <button wire:click="unfreezeWallet({{ $wallet->id }})"
                                    class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs transition-colors">
                                    Unfreeze
                                </button>
                                @else
                                <button onclick="const reason = prompt('Enter freezing reason:'); if (reason) { $wire.freezeWallet({{ $wallet->id }}, reason) }"
                                    class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs transition-colors">
                                    Freeze
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-medium">No wallets registered yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Dispute Resolution and ledger alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
            {{-- Invoices feed --}}
            <div class="lg:col-span-2 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/40">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Recent Paid Invoices</h3>
                    <a href="{{ \App\Filament\Resources\InvoiceResource::getUrl() }}" class="text-xs font-semibold text-primary-600 hover:underline">View All →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50/50 dark:bg-gray-900/50">
                            <tr class="text-xs text-gray-500 font-semibold uppercase">
                                <th class="px-6 py-3.5 text-left">Invoice #</th>
                                <th class="px-6 py-3.5 text-left">User</th>
                                <th class="px-6 py-3.5 text-left">Type</th>
                                <th class="px-6 py-3.5 text-left">Amount</th>
                                <th class="px-6 py-3.5 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($this->stats['recent_invoices'] ?? [] as $invoice)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors font-medium">
                                <td class="px-6 py-4 font-bold text-primary-600">
                                    <a href="{{ \App\Filament\Resources\InvoiceResource::getUrl('view', ['record' => $invoice]) }}">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $invoice->user?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded text-3xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                        {{ ucwords(str_replace('_', ' ', $invoice->type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">৳{{ number_format($invoice->total_amount, 2) }}</td>
                                <td class="px-6 py-4">
                                    @php $badge = $invoice->statusBadge(); @endphp
                                    <span class="px-2 py-0.5 rounded text-3xs font-bold uppercase
                                        {{ match($badge['color']) {
                                            'success' => 'bg-emerald-100 text-emerald-800',
                                            'warning' => 'bg-amber-100 text-amber-800',
                                            'danger'  => 'bg-rose-100 text-rose-800',
                                            default   => 'bg-gray-100 text-gray-700',
                                        } }}">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">No invoices yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Dispute Manager --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Dispute & Failed deduction Tracker</h3>
                    </div>
                    
                    <div class="divide-y divide-gray-150 dark:divide-gray-700 overflow-y-auto max-h-96">
                        @forelse($this->disputedTransactions as $tx)
                        <div class="p-4 font-medium hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $tx->status === 'disputed' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ strtoupper($tx->status) }}
                                    </span>
                                    <h5 class="text-xs font-bold text-gray-900 dark:text-white mt-1.5">{{ $tx->wallet?->user?->name }}</h5>
                                </div>
                                <span class="font-bold text-rose-600 dark:text-rose-400 text-sm">-৳{{ number_format($tx->amount, 2) }}</span>
                            </div>
                            <p class="text-2xs text-gray-450 mt-1 leading-relaxed">{{ $tx->description }}</p>
                            
                            @if($tx->status === 'disputed')
                            <button wire:click="resolveDispute({{ $tx->id }})" class="mt-3 w-full py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 rounded-lg text-2xs transition-colors">
                                Resolve & Complete
                            </button>
                            @endif
                        </div>
                        @empty
                        <div class="p-6 text-center text-gray-400">
                            <x-heroicon-o-shield-check class="h-10 w-10 mx-auto text-gray-300 mb-2" />
                            <p class="text-xs font-bold">No active disputes logged.</p>
                            <p class="text-3xs text-gray-450 mt-0.5">Billing logs running cleanly.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <div class="p-4 border-t border-gray-150 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-800/40 text-center">
                    <a href="{{ url('/admin/finance/ledgers') }}" class="text-xs font-bold text-primary-600 hover:underline">
                        Open Full Transaction Ledger →
                    </a>
                </div>
            </div>
        </div>

        {{-- Alpine Action Modal UI Overlay --}}
        <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity" style="display: none;">
            <div @click.away="modalOpen = false" class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-750 p-6 relative">
                <button @click="modalOpen = false" class="absolute top-4 right-4 text-gray-450 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="h-6 w-6" />
                </button>
                
                <h4 class="text-lg font-bold text-gray-900 dark:text-white capitalize mb-1" x-text="modalType + ' Wallet Balance'"></h4 >
                <p class="text-xs text-gray-450 mb-5">Employer: <span class="font-semibold text-gray-700 dark:text-gray-300" x-text="employerName"></span></p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Adjustment Amount (BDT)</label>
                        <input x-model="amount" type="number" step="0.01" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 text-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 text-gray-900 dark:text-white" placeholder="0.00" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Reason / Justification</label>
                        <textarea x-model="reason" rows="3" class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 text-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 text-gray-900 dark:text-white" placeholder="Describe the reason for override..."></textarea>
                    </div>

                    <div class="flex gap-2.5 pt-4">
                        <button @click="modalOpen = false" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-850 dark:text-white rounded-xl text-xs font-bold transition-all">
                            Cancel
                        </button>
                        <button @click="submitAction" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all">
                            Confirm Adjust
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
