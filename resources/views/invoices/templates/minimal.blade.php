<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #374151; background: #fff; }
        .page { padding: 48px; max-width: 860px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 48px; }
        .company-name { font-size: 18px; font-weight: 800; color: #111827; }
        .company-info { font-size: 11px; color: #9ca3af; margin-top: 4px; line-height: 1.6; }
        .invoice-right { text-align: right; }
        .label-invoice { font-size: 32px; font-weight: 900; color: #f3f4f6; letter-spacing: -1px; }
        .invoice-num { font-size: 13px; font-weight: 700; color: #111827; margin-top: 4px; }
        .status-pill {
            display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 4px;
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;
            border: 1.5px solid {{ $primary_color ?? '#1a56db' }};
            color: {{ $primary_color ?? '#1a56db' }};
        }
        hr.divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .billing-row { display: flex; gap: 40px; margin-bottom: 36px; }
        .bill-block h5 { font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; color: #9ca3af; margin-bottom: 6px; }
        .bill-block p { line-height: 1.7; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items th { text-align: left; padding: 8px 12px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; border-bottom: 1.5px solid #e5e7eb; }
        table.items th:last-child { text-align: right; }
        table.items td { padding: 12px 12px; border-bottom: 1px solid #f9fafb; vertical-align: top; }
        table.items td:last-child { text-align: right; font-weight: 600; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-inner { width: 240px; }
        .totals-inner .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; }
        .totals-inner .grand { border-top: 2px solid #111827; margin-top: 8px; padding-top: 8px; font-size: 15px; font-weight: 800; }
        .footer { margin-top: 48px; font-size: 10px; color: #d1d5db; text-align: center; border-top: 1px solid #f3f4f6; padding-top: 16px; }
        {!! $custom_css ?? '' !!}
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            @if(!empty($logo_url))
                <img src="{{ $logo_url }}" alt="" style="max-height:50px; max-width:160px; margin-bottom:8px; display:block;">
            @endif
            <div class="company-name">{{ $company_name }}</div>
            <div class="company-info">
                @if($company_email){{ $company_email }}<br>@endif
                @if($company_phone){{ $company_phone }}<br>@endif
                @if($company_address){!! nl2br(e($company_address)) !!}@endif
            </div>
        </div>
        <div class="invoice-right">
            <div class="label-invoice">INV</div>
            <div class="invoice-num">{{ $invoice->invoice_number }}</div>
            <div class="status-pill">{{ $status_label }}</div>
        </div>
    </div>

    <hr class="divider">

    <div class="billing-row">
        <div class="bill-block">
            <h5>Bill To</h5>
            <p>
                <strong>{{ $invoice->billing_name ?? $invoice->user?->name }}</strong><br>
                @if($invoice->billing_company){{ $invoice->billing_company }}<br>@endif
                @if($invoice->billing_email){{ $invoice->billing_email }}<br>@endif
                @if($invoice->billing_address){{ $invoice->billing_address }}<br>@endif
                @if($invoice->billing_country){{ $invoice->billing_country }}@endif
            </p>
        </div>
        <div class="bill-block">
            <h5>Details</h5>
            <p>
                <strong>Issued:</strong> {{ $invoice->issued_at?->format('M d, Y') ?? now()->format('M d, Y') }}<br>
                <strong>Due:</strong> {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}<br>
                <strong>Type:</strong> {{ $type_label }}<br>
                <strong>Currency:</strong> {{ $currency }}
            </p>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $currency_symbol }}{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ $currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-inner">
            <div class="row"><span>Subtotal</span><span>{{ $currency_symbol }}{{ $subtotal_formatted }}</span></div>
            @if($invoice->tax_amount > 0)
            <div class="row"><span>{{ $tax_label }}</span><span>{{ $currency_symbol }}{{ $tax_formatted }}</span></div>
            @endif
            @if($invoice->discount_amount > 0)
            <div class="row" style="color:#dc2626;"><span>Discount</span><span>- {{ $currency_symbol }}{{ $discount_formatted }}</span></div>
            @endif
            @if($invoice->platform_fee > 0)
            <div class="row"><span>Platform Fee</span><span>{{ $currency_symbol }}{{ number_format($invoice->platform_fee, 2) }}</span></div>
            @endif
            <div class="row grand"><span>Total</span><span>{{ $currency_symbol }}{{ $total_formatted }}</span></div>
        </div>
    </div>

    @if($invoice->notes || !empty($payment_instructions))
    <hr class="divider">
    @if($invoice->notes)
    <p style="font-size:11px; color:#6b7280; margin-bottom:8px;"><strong>Notes:</strong> {{ $invoice->notes }}</p>
    @endif
    @if(!empty($payment_instructions))
    <p style="font-size:11px; color:#6b7280;"><strong>Payment Instructions:</strong> {!! nl2br(e($payment_instructions)) !!}</p>
    @endif
    @endif

    <div class="footer">
        {{ $company_name }}
        @if(!empty($footer_text)) · {{ $footer_text }} @endif
        · Generated {{ now()->format('M d, Y') }}
    </div>
</div>
</body>
</html>
