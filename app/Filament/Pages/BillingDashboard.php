<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Wallet;
use App\Models\Promotion;
use App\Models\WalletTransaction;
use App\Services\Ad\AdAuditService;
use App\Services\Billing\InvoiceService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Filament\Widgets\InvoiceRevenueChart;
use App\Filament\Widgets\InvoiceStatusOverview;

class BillingDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Billing Dashboard';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int    $navigationSort  = 0;
    protected static ?string $title           = 'Billing & Finance Dashboard';
    protected static string  $view            = 'filament.pages.billing-dashboard';

    public array $stats = [];
    public $wallets = [];
    public $disputedTransactions = [];

    // Fields for interactive modals
    public $selectedWalletId;
    public $selectedPromoId;
    public $actionAmount;
    public $actionReason;

    public function mount(): void
    {
        $this->loadStats();
        $this->loadWalletsAndDisputes();
    }

    public function loadStats(): void
    {
        $this->stats = [
            'total_revenue_month'    => Invoice::paid()->thisMonth()->sum('total_amount'),
            'total_revenue_all_time' => Invoice::paid()->sum('total_amount'),
            'pending_count'          => Invoice::pending()->count(),
            'pending_value'          => Invoice::pending()->sum('total_amount'),
            'overdue_count'          => Invoice::overdue()->count(),
            'overdue_value'          => Invoice::overdue()->sum('total_amount'),
            'paid_count_month'       => Invoice::paid()->thisMonth()->count(),
            'refunded_month'         => Invoice::byStatus('refunded')->thisMonth()->sum('total_amount'),

            // By type (this month)
            'subscription_revenue'   => Invoice::byType('subscription')->paid()->thisMonth()->sum('total_amount'),
            'boost_revenue'          => Invoice::byType('job_boost')->paid()->thisMonth()->sum('total_amount'),
            'milestone_revenue'      => Invoice::byType('milestone')->paid()->thisMonth()->sum('total_amount'),
            'escrow_funded_month'    => Invoice::byType('escrow_funding')->paid()->thisMonth()->sum('total_amount'),

            // Recent invoices
            'recent_invoices'        => Invoice::with('user')->latest()->limit(5)->get(),
        ];
    }

    public function loadWalletsAndDisputes(): void
    {
        $this->wallets = Wallet::with('user')
            ->orderByDesc('balance')
            ->get();

        $this->disputedTransactions = WalletTransaction::with('wallet.user')
            ->where('description', 'like', '%disputed%')
            ->orWhere('status', 'failed')
            ->orWhere('reference_type', 'failed_lock')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function getWidgets(): array
    {
        return [
            InvoiceStatusOverview::class,
            InvoiceRevenueChart::class,
        ];
    }

    /**
     * Freeze Employer Wallet
     */
    public function freezeWallet(int $walletId, string $reason): void
    {
        $wallet = Wallet::find($walletId);
        if ($wallet) {
            $wallet->update([
                'is_frozen' => true,
                'freeze_reason' => $reason
            ]);

            // Disable all campaigns of this employer
            Promotion::where('user_id', $wallet->user_id)
                ->where('status', 'active')
                ->update(['status' => 'paused']);

            AdAuditService::logAction('wallet_freeze', $walletId, ['is_frozen' => false], ['is_frozen' => true], $reason);
            Notification::make()->title('Employer Wallet Frozen successfully. Campaigns paused.')->success()->send();
            
            $this->loadWalletsAndDisputes();
        }
    }

    /**
     * Unfreeze Employer Wallet
     */
    public function unfreezeWallet(int $walletId): void
    {
        $wallet = Wallet::find($walletId);
        if ($wallet) {
            $wallet->update([
                'is_frozen' => false,
                'freeze_reason' => null
            ]);

            AdAuditService::logAction('wallet_unfreeze', $walletId, ['is_frozen' => true], ['is_frozen' => false], 'Admin manually unfroze wallet.');
            Notification::make()->title('Employer Wallet Unfrozen.')->success()->send();
            
            $this->loadWalletsAndDisputes();
        }
    }

    /**
     * Force Deduct Wallet Balance (Admin Override)
     */
    public function forceDeduct(int $walletId, float $amount, string $reason): void
    {
        $wallet = Wallet::find($walletId);
        if ($wallet) {
            if ($wallet->balance < $amount) {
                Notification::make()->title('Failed: Insufficient Wallet Balance!')->danger()->send();
                return;
            }

            DB::transaction(function () use ($wallet, $amount, $reason, $walletId) {
                $wallet->debit($amount, 'admin_force_deduct', null, $reason);
                AdAuditService::logAction('wallet_force_deduct', $walletId, ['balance' => $wallet->balance + $amount], ['balance' => $wallet->balance], $reason);
            });

            Notification::make()->title("৳{$amount} deducted successfully.")->success()->send();
            $this->loadWalletsAndDisputes();
        }
    }

    /**
     * Refund Campaign Spend
     */
    public function refundCampaign(int $walletId, float $amount, string $reason): void
    {
        $wallet = Wallet::find($walletId);
        if ($wallet) {
            DB::transaction(function () use ($wallet, $amount, $reason, $walletId) {
                $wallet->credit($amount, 'admin_campaign_refund', null, $reason);
                AdAuditService::logAction('wallet_refund', $walletId, ['balance' => $wallet->balance - $amount], ['balance' => $wallet->balance], $reason);
            });

            Notification::make()->title("৳{$amount} refunded to wallet successfully.")->success()->send();
            $this->loadWalletsAndDisputes();
        }
    }

    /**
     * Adjust Billing Dispute (Set status to resolved / completed)
     */
    public function resolveDispute(int $transactionId): void
    {
        $tx = WalletTransaction::find($transactionId);
        if ($tx) {
            $tx->update([
                'status' => 'completed',
                'description' => str_replace('disputed', 'resolved', $tx->description)
            ]);
            Notification::make()->title('Disputed transaction marked resolved and completed.')->success()->send();
            $this->loadWalletsAndDisputes();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_invoice')
                ->label('Create Manual Invoice')
                ->icon('heroicon-o-plus')
                ->url(\App\Filament\Resources\InvoiceResource::getUrl('create')),

            Action::make('mark_overdue')
                ->label('Mark Overdue Invoices')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $count = app(InvoiceService::class)->markOverdueInvoices();
                    $this->loadStats();
                    Notification::make()->title("{$count} invoices marked as overdue.")->success()->send();
                }),

            Action::make('export_invoices')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $invoices = Invoice::with('user')->get();
                    $csv = "Invoice #,Type,User,Total,Status,Date\n";
                    foreach ($invoices as $inv) {
                        $csv .= "\"{$inv->invoice_number}\",\"{$inv->type}\",\"{$inv->user?->name}\",\"{$inv->total_amount}\",\"{$inv->status}\",\"{$inv->created_at->toDateString()}\"\n";
                    }
                    return response()->streamDownload(fn() => print($csv), 'invoices-export-' . now()->format('Y-m-d') . '.csv');
                }),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_billing_dashboard');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_billing_dashboard');
    }
}
