<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    /**
     * Get all purchases for the authenticated user.
     * Aggregates: invoices, subscriptions, and CV template purchases.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $type = $request->query('type', 'all'); // all, invoices, subscriptions, cv_templates
            $limit = $request->integer('limit', 50);

            $purchases = collect();

            // --- Invoice History ---
            if (in_array($type, ['all', 'invoices'])) {
                $invoices = $user->invoices()
                    ->with('items')
                    ->latest()
                    ->take($limit)
                    ->get()
                    ->map(fn ($invoice) => [
                        'id' => $invoice->id,
                        'type' => 'invoice',
                        'category' => $invoice->type,
                        'title' => $invoice->typeLabel(),
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => (float) $invoice->total_amount,
                        'currency' => $invoice->currency_code,
                        'status' => $invoice->status,
                        'status_badge' => $invoice->statusBadge(),
                        'description' => $invoice->notes,
                        'items' => $invoice->items->map(fn ($item) => [
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'total' => (float) $item->total,
                        ]),
                        'created_at' => $invoice->created_at->toIso8601String(),
                        'paid_at' => $invoice->paid_at?->toIso8601String(),
                    ]);

                $purchases = $purchases->concat($invoices);
            }

            // --- Subscription History ---
            if (in_array($type, ['all', 'subscriptions'])) {
                $subscriptions = $user->subscriptions()
                    ->with('plan')
                    ->latest()
                    ->take($limit)
                    ->get()
                    ->map(fn ($sub) => [
                        'id' => $sub->id,
                        'type' => 'subscription',
                        'category' => 'subscription',
                        'title' => $sub->plan?->name ?? 'Subscription Plan',
                        'plan_name' => $sub->plan?->name,
                        'amount' => (float) ($sub->plan_details['amount'] ?? $sub->plan?->price ?? 0),
                        'currency' => $sub->plan_details['currency'] ?? $sub->plan?->currency ?? 'BDT',
                        'status' => $sub->status,
                        'billing_cycle' => $sub->billing_cycle,
                        'starts_at' => $sub->starts_at?->toIso8601String(),
                        'expires_at' => $sub->expires_at?->toIso8601String(),
                        'is_recurring' => $sub->is_recurring,
                        'created_at' => $sub->created_at->toIso8601String(),
                    ]);

                $purchases = $purchases->concat($subscriptions);
            }

            // --- CV Template Purchases (invoices with type containing cv/template) ---
            if (in_array($type, ['all', 'cv_templates'])) {
                $cvPurchases = $user->invoices()
                    ->whereIn('type', ['cv_template', 'cv_generation', 'ai_cv', 'template_purchase'])
                    ->with('items')
                    ->latest()
                    ->take($limit)
                    ->get()
                    ->map(fn ($invoice) => [
                        'id' => $invoice->id,
                        'type' => 'cv_template',
                        'category' => 'cv_template',
                        'title' => $invoice->notes ?? 'CV Template Purchase',
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => (float) $invoice->total_amount,
                        'currency' => $invoice->currency_code,
                        'status' => $invoice->status,
                        'status_badge' => $invoice->statusBadge(),
                        'created_at' => $invoice->created_at->toIso8601String(),
                        'paid_at' => $invoice->paid_at?->toIso8601String(),
                    ]);

                $purchases = $purchases->concat($cvPurchases);
            }

            // Sort by created_at descending and paginate
            $purchases = $purchases->sortByDesc('created_at')->values()->take($limit);

            // Summary stats
            $activeSub = $user->activeSubscription();
            $summary = [
                'total_invoices' => $user->invoices()->count(),
                'total_invoices_paid' => $user->invoices()->where('status', 'paid')->count(),
                'total_spent' => (float) $user->invoices()->where('status', 'paid')->sum('total_amount'),
                'active_subscriptions' => $user->subscriptions()->active()->count(),
                'has_active_plan' => $activeSub !== null,
                'plan_name' => $activeSub?->plan?->name,
            ];

            return response()->json([
                'status' => true,
                'data' => $purchases,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load purchases: ' . $e->getMessage(),
            ], 500);
        }
    }
}
