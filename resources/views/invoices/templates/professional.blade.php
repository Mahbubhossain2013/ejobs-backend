<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }} | {{ $company_name }}</title>
    <style>
        @if(!empty($watermark_text))
        @page { size: A4; }
        @endif
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; }
        .page { max-width: 900px; margin: 0 auto; }
        .top-bar { background: {{ $primary_color ?? '#1a56db' }}; padding: 20px 48px; display: flex; justify-content: space-between; align-items: center; }
        .top-bar .company { color: #fff; }
        .top-bar .company h1 { font-size: 20px; font-weight: 900; letter-spacing: -0.5px; }
        .top-bar .company p { font-size: 11px; opacity: 0.8; margin-top: 2px; }
        .top-bar .inv-badge { text-align: right; color: #fff; }
        .top-bar .inv-badge .word { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; }
        .top-bar .inv-badge .num { font-size: 22px; font-weight: 900; letter-spacing: -0.5px; }
        .top-bar .inv-badge .status {
            display: inline-block; margin-top: 4px; padding: 2px 10px;
            border-radius: 99px; font-size: 10px; font-weight: 700; text-transform: uppercase;
            background: rgba(255,255,255,0.25); letter-spacing: 1px;
        }
        .body-area { padding: 40px 48px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0; border: 1.5px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 36px; }
        .info-grid .cell { padding: 14px 18px; border-right: 1px solid #e5e7eb; }
        .info-grid .cell:last-child { border-right: none; }
        .info-grid .cell label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #9ca3af; display: block; margin-bottom: 4px; }
        .info-grid .cell span { font-size: 13px; font-weight: 700; color: #111827; }
        .bill-section { display: flex; gap: 40px; margin-bottom: 36px; }
        .bill-box { flex: 1; background: #f9fafb; border-radius: 10px; padding: 18px 20px; border: 1px solid #f3f4f6; }
        .bill-box h4 { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: {{ $primary_color ?? '#1a56db' }}; margin-bottom: 10px; }
        .bill-box p { line-height: 1.7; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.items thead tr { background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
        table.items thead th { padding: 10px 14px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
        table.items thead th:last-child { text-align: right; }
        table.items tbody tr { border-bottom: 1px solid #f3f4f6; }
        table.items tbody tr:hover { background: #fafafa; }
        table.items tbody td { padding: 13px 14px; vertical-align: top; }
        table.items tbody td:last-child { text-align: right; font-weight: 700; }
        .desc-detail { font-size: 10px; color: #9ca3af; margin-top: 2px; }
        .totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 32px; }
        .totals-box { width: 290px; background: #f9fafb; border-radius: 10px; padding: 18px 20px; border: 1px solid #f3f4f6; }
        .t-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px; }
        .t-row .lbl { color: #6b7280; }
        .t-row.grand { border-top: 2px solid {{ $primary_color ?? '#1a56db' }}; margin-top: 10px; padding-top: 12px; font-size: 16px; font-weight: 900; color: {{ $primary_color ?? '#1a56db' }}; }
        .notes-area { background: {{ $secondary_color ?? '#e1effe' }}30; border: 1px solid {{ $secondary_color ?? '#e1effe' }}; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
        .notes-area h4 { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: {{ $primary_color ?? '#1a56db' }}; margin-bottom: 6px; }
        .bottom-bar { background: #f9fafb; padding: 16px 48px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 10px; color: #9ca3af; }
        @if(!empty($watermark_text))
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 80px; font-weight: 900; color: rgba(0,0,0,0.04); pointer-events: none; z-index: 0; white-space: nowrap; }
        @endif
        {!! $custom_css ?? '' !!}
    </style>
</head>
<body>
@if(!empty($watermark_text))
<div class="watermark">{{ strtoupper($watermark_text) }}</div>
@endif
<div class="page">
    {{-- TOP BAR --}}
    <div class="top-bar">
        <div class="company">
            @if(!empty($logo_url))
                <img src="{{ $logo_url }}" alt="" style="max-height:44px; margin-bottom:4px; display:block;">
            @endif
            <h1>{{ $company_name }}</h1>
            <p>{{ $company_email }}{{ $company_phone ? ' · '.$company_phone : '' }}</p>
        </div>
        <div class="inv-badge">
            <div class="word">Invoice</div>
            <div class="num">{{ $invoice->invoice_number }}</div>
            <div class="status">{{ $status_label }}</div>
        </div>
    </div>

    {{-- BODY --}}
    <div class="body-area">
        {{-- INFO GRID --}}
        <div class="info-grid">
            <div class="cell"><label>Issue Date</label><span>{{ $invoice->issued_at?->format('M d, Y') ?? now()->format('M d, Y') }}</span></div>
            <div class="cell"><label>Due Date</label><span @if($invoice->isOverdue()) style="color:#dc2626;" @endif>{{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</span></div>
            <div class="cell"><label>Invoice Type</label><span>{{ $type_label }}</span></div>
            <div class="cell"><label>Currency</label><span>{{ $currency }}</span></div>
        </div>

        {{-- BILL TO --}}
        <div class="bill-section">
            <div class="bill-box">
                <h4>Bill To</h4>
                <p>
                    <strong>{{ $invoice->billing_name ?? $invoice->user?->name }}</strong><br>
                    @if($invoice->billing_company)<em>{{ $invoice->billing_company }}</em><br>@endif
                    @if($invoice->billing_email){{ $invoice->billing_email }}<br>@endif
                    @if($invoice->billing_address){!! nl2br(e($invoice->billing_address)) !!}<br>@endif
                    @if($invoice->billing_vat_number)VAT: {{ $invoice->billing_vat_number }}<br>@endif
                    @if($invoice->billing_country){{ $invoice->billing_country }}@endif
                </p>
            </div>
            @if($invoice->payment_method)
            <div class="bill-box">
                <h4>Payment Info</h4>
                <p>
                    <strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->payment_method)) }}<br>
                    @if($invoice->paid_at)<strong>Paid:</strong> {{ $invoice->paid_at->format('M d, Y') }}<br>@endif
                    @if($invoice->payment_transaction_id)<strong>Ref:</strong> {{ $invoice->payment_transaction_id }}@endif
                </p>
            </div>
            @endif
        </div>

        {{-- ITEMS --}}
        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="width:60px;">Qty</th>
                    <th style="width:100px;">Unit Price</th>
                    @if($invoice->tax_rate > 0)<th style="width:70px;">{{ $tax_label }}</th>@endif
                    <th style="width:100px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->description }}</strong>
                        @if($item->details)<div class="desc-detail">{{ $item->details }}</div>@endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $currency_symbol }}{{ number_format($item->unit_price, 2) }}</td>
                    @if($invoice->tax_rate > 0)<td>{{ $item->tax_rate }}%</td>@endif
                    <td>{{ $currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TOTALS --}}
        <div class="totals-wrap">
            <div class="totals-box">
                <div class="t-row"><span class="lbl">Subtotal</span><span>{{ $currency_symbol }}{{ $subtotal_formatted }}</span></div>
                @if($invoice->discount_amount > 0)
                <div class="t-row" style="color:#dc2626;"><span class="lbl">Discount</span><span>- {{ $currency_symbol }}{{ $discount_formatted }}</span></div>
                @endif
                @if($invoice->tax_amount > 0)
                <div class="t-row"><span class="lbl">{{ $tax_label }} ({{ $invoice->tax_rate }}%)</span><span>{{ $currency_symbol }}{{ $tax_formatted }}</span></div>
                @endif
                @if($invoice->platform_fee > 0)
                <div class="t-row"><span class="lbl">Platform Fee</span><span>{{ $currency_symbol }}{{ number_format($invoice->platform_fee, 2) }}</span></div>
                @endif
                <div class="t-row grand"><span>Total Due</span><span>{{ $currency_symbol }}{{ $total_formatted }}</span></div>
            </div>
        </div>

        {{-- NOTES / PAYMENT INSTRUCTIONS --}}
        @if($invoice->notes || !empty($payment_instructions) || !empty($terms))
        <div class="notes-area">
            @if($invoice->notes)
            <h4>Notes</h4>
            <p style="margin-bottom:8px; font-size:11px;">{!! nl2br(e($invoice->notes)) !!}</p>
            @endif
            @if(!empty($payment_instructions))
            <h4>Payment Instructions</h4>
            <p style="margin-bottom:8px; font-size:11px;">{!! nl2br(e($payment_instructions)) !!}</p>
            @endif
            @if(!empty($terms))
            <h4>Terms & Conditions</h4>
            <p style="font-size:10px; color:#6b7280;">{!! nl2br(e($terms)) !!}</p>
            @endif
        </div>
        @endif
    </div>

    {{-- BOTTOM BAR --}}
    <div class="bottom-bar">
        <strong>{{ $company_name }}</strong>
        @if(!empty($footer_text)) · {{ $footer_text }} @endif
        · Invoice #{{ $invoice->invoice_number }} · Generated {{ now()->format('M d, Y') }}
    </div>
</div>
</body>
</html>
