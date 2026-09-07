<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        /* ── Reset & Base ─────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: {{ $text_color ?? '#111827' }};
            background: #fff;
            line-height: 1.5;
        }

        /* ── Watermark ────────────────────────────────── */
        @if(!empty($watermark_text))
        .watermark {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translateX(-50%) translateY(-50%) rotate(-35deg);
            font-size: 72px;
            font-weight: 900;
            color: rgba(0,0,0,0.06);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            letter-spacing: 12px;
        }
        @endif

        .page { position: relative; padding: 40px 48px; max-width: 900px; margin: 0 auto; }

        /* ── Header ───────────────────────────────────── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 3px solid {{ $primary_color ?? '#1a56db' }};
        }
        .logo img { max-height: 60px; max-width: 180px; }
        .logo-text {
            font-size: 22px;
            font-weight: 800;
            color: {{ $primary_color ?? '#1a56db' }};
        }
        .invoice-meta { text-align: right; }
        .invoice-title {
            font-size: 30px;
            font-weight: 800;
            color: {{ $primary_color ?? '#1a56db' }};
            letter-spacing: -0.5px;
        }
        .invoice-number {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }
        .invoice-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: {{ match($invoice->status) {
                'paid'    => '#d1fae5',
                'overdue' => '#fee2e2',
                'pending' => '#fef3c7',
                'refunded'=> '#f3f4f6',
                default   => '#e0e7ff',
            } }};
            color: {{ match($invoice->status) {
                'paid'    => '#065f46',
                'overdue' => '#991b1b',
                'pending' => '#92400e',
                'refunded'=> '#374151',
                default   => '#3730a3',
            } }};
        }

        /* ── Addresses ────────────────────────────────── */
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 36px;
            gap: 40px;
        }
        .address-block { flex: 1; }
        .address-block h4 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .address-block p { line-height: 1.7; }
        .address-block strong { font-weight: 700; font-size: 14px; }

        /* ── Invoice Info Strip ───────────────────────── */
        .info-strip {
            display: flex;
            gap: 0;
            margin-bottom: 36px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .info-cell {
            flex: 1;
            padding: 14px 20px;
            border-right: 1px solid #e5e7eb;
            background: {{ $secondary_color ?? '#e1effe' }}30;
        }
        .info-cell:last-child { border-right: none; }
        .info-cell label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            display: block;
            margin-bottom: 4px;
        }
        .info-cell span {
            font-size: 14px;
            font-weight: 600;
        }

        /* ── Line Items Table ─────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .items-table thead tr {
            background: {{ $primary_color ?? '#1a56db' }};
            color: #fff;
        }
        .items-table thead th {
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .items-table thead th:last-child { text-align: right; }
        .items-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .items-table tbody tr:nth-child(even) { background: #f9fafb; }
        .items-table tbody td {
            padding: 14px 16px;
            vertical-align: top;
        }
        .items-table tbody td:last-child { text-align: right; font-weight: 600; }
        .item-details { font-size: 11px; color: #9ca3af; margin-top: 2px; }

        /* ── Totals ───────────────────────────────────── */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 32px;
        }
        .totals-table { width: 280px; }
        .totals-table tr td { padding: 6px 0; }
        .totals-table .label { color: #6b7280; font-size: 13px; }
        .totals-table .value { text-align: right; font-weight: 600; }
        .totals-table .total-row td {
            padding-top: 12px;
            border-top: 2px solid {{ $primary_color ?? '#1a56db' }};
            font-size: 16px;
            font-weight: 800;
            color: {{ $primary_color ?? '#1a56db' }};
        }

        /* ── Notes / Terms ────────────────────────────── */
        .section-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }
        .section-box h4 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ $primary_color ?? '#1a56db' }};
            margin-bottom: 8px;
        }
        .section-box p { color: #4b5563; line-height: 1.7; }

        /* ── Footer ───────────────────────────────────── */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            line-height: 1.8;
        }

        {!! $custom_css ?? '' !!}
    </style>
</head>
<body>
<div class="page">
    @if(!empty($watermark_text))
    <div class="watermark">{{ $watermark_text }}</div>
    @endif

    {{-- HEADER --}}
    <div class="header">
        <div class="logo">
            @if(!empty($logo_url))
                <img src="{{ $logo_url }}" alt="{{ $company_name }}">
            @else
                <div class="logo-text">{{ $company_name }}</div>
            @endif
            @if(!empty($company_address))
                <p style="font-size:11px; color:#6b7280; margin-top:6px; line-height:1.6;">
                    {!! nl2br(e($company_address)) !!}<br>
                    @if(!empty($company_email)){{ $company_email }}<br>@endif
                    @if(!empty($company_phone)){{ $company_phone }}@endif
                </p>
            @endif
        </div>
        <div class="invoice-meta">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number"># {{ $invoice->invoice_number }}</div>
            <div class="invoice-badge">{{ $status_label }}</div>
        </div>
    </div>

    {{-- BILLING / FROM --}}
    <div class="addresses">
        <div class="address-block">
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
        <div class="address-block" style="text-align:right;">
            <h4>Invoice Info</h4>
            <p>
                <strong>Type:</strong> {{ $type_label }}<br>
                <strong>Currency:</strong> {{ $currency }}<br>
            </p>
        </div>
    </div>

    {{-- INFO STRIP --}}
    <div class="info-strip">
        <div class="info-cell">
            <label>Issue Date</label>
            <span>{{ $invoice->issued_at?->format('M d, Y') ?? now()->format('M d, Y') }}</span>
        </div>
        <div class="info-cell">
            <label>Due Date</label>
            <span @if($invoice->isOverdue()) style="color:#dc2626;" @endif>
                {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}
            </span>
        </div>
        @if($invoice->paid_at)
        <div class="info-cell">
            <label>Paid On</label>
            <span style="color:#059669;">{{ $invoice->paid_at->format('M d, Y') }}</span>
        </div>
        @endif
        <div class="info-cell">
            <label>Payment Method</label>
            <span>{{ ucfirst(str_replace('_', ' ', $invoice->payment_method ?? 'Pending')) }}</span>
        </div>
    </div>

    {{-- LINE ITEMS --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:45%;">Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                @if($invoice->tax_rate > 0)<th>{{ $tax_label }}</th>@endif
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    {{ $item->description }}
                    @if($item->details)
                        <div class="item-details">{{ $item->details }}</div>
                    @endif
                </td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $currency_symbol }}{{ number_format($item->unit_price, 2) }}</td>
                @if($invoice->tax_rate > 0)
                    <td>{{ $item->tax_rate }}%</td>
                @endif
                <td>{{ $currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ $currency_symbol }}{{ $subtotal_formatted }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
            <tr>
                <td class="label">Discount</td>
                <td class="value" style="color:#dc2626;">- {{ $currency_symbol }}{{ $discount_formatted }}</td>
            </tr>
            @endif
            @if($invoice->tax_amount > 0)
            <tr>
                <td class="label">{{ $tax_label }} ({{ $invoice->tax_rate }}%)</td>
                <td class="value">{{ $currency_symbol }}{{ $tax_formatted }}</td>
            </tr>
            @endif
            @if($invoice->platform_fee > 0)
            <tr>
                <td class="label">Platform Fee</td>
                <td class="value">{{ $currency_symbol }}{{ number_format($invoice->platform_fee, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="label">Total Due</td>
                <td class="value">{{ $currency_symbol }}{{ $total_formatted }}</td>
            </tr>
            @if($invoice->amount_paid > 0 && $invoice->status !== 'paid')
            <tr>
                <td class="label" style="color:#059669;">Amount Paid</td>
                <td class="value" style="color:#059669;">- {{ $currency_symbol }}{{ number_format($invoice->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td class="label" style="font-weight:700;">Balance Due</td>
                <td class="value" style="font-weight:800; color:#dc2626;">{{ $currency_symbol }}{{ number_format($invoice->amount_due, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- NOTES --}}
    @if($invoice->notes)
    <div class="section-box">
        <h4>Notes</h4>
        <p>{!! nl2br(e($invoice->notes)) !!}</p>
    </div>
    @endif

    {{-- PAYMENT INSTRUCTIONS --}}
    @if(!empty($payment_instructions))
    <div class="section-box" style="background: {{ $secondary_color ?? '#e1effe' }}20;">
        <h4>Payment Instructions</h4>
        <p>{!! nl2br(e($payment_instructions)) !!}</p>
    </div>
    @endif

    {{-- TERMS --}}
    @if(!empty($terms))
    <div class="section-box">
        <h4>Terms & Conditions</h4>
        <p style="font-size:11px;">{!! nl2br(e($terms)) !!}</p>
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <p>
            <strong>{{ $company_name }}</strong>
            @if(!empty($company_email)) · {{ $company_email }} @endif
            @if(!empty($company_website)) · {{ $company_website }} @endif
        </p>
        @if(!empty($footer_text))
        <p>{{ $footer_text }}</p>
        @endif
        <p>Generated {{ now()->format('M d, Y \a\t H:i') }}</p>
    </div>
</div>
</body>
</html>
