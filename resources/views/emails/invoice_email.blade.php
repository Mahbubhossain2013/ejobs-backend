<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #374151; line-height: 1.6; margin: 0; padding: 0; background: #f9fafb; }
        .container { max-width: 680px; margin: 0 auto; background: #fff; }
        .email-header { background: {{ $primary_color ?? '#1a56db' }}; padding: 32px 40px; text-align: center; }
        .email-header img { max-height: 48px; margin-bottom: 12px; }
        .email-header h1 { color: #fff; font-size: 20px; font-weight: 800; margin: 0; }
        .email-header p { color: rgba(255,255,255,0.8); font-size: 13px; margin: 4px 0 0; }
        .body { padding: 32px 40px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .invoice-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; margin-bottom: 24px; }
        .invoice-box table { width: 100%; border-collapse: collapse; }
        .invoice-box td { padding: 8px 0; }
        .invoice-box .label { color: #6b7280; font-size: 13px; width: 50%; }
        .invoice-box .value { font-weight: 600; text-align: right; }
        .amount-big { text-align: center; font-size: 32px; font-weight: 900; color: {{ $primary_color ?? '#1a56db' }}; margin: 20px 0 8px; }
        .amount-label { text-align: center; font-size: 12px; color: #6b7280; margin-bottom: 20px; }
        .cta-btn { display: block; width: 200px; margin: 24px auto; padding: 14px 28px; background: {{ $primary_color ?? '#1a56db' }}; color: #fff; text-decoration: none; border-radius: 8px; text-align: center; font-weight: 700; font-size: 14px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .message-block { font-size: 13px; color: #6b7280; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="container">
    {{-- Header --}}
    <div class="email-header">
        @if(!empty($logo_url))
            <img src="{{ $logo_url }}" alt="{{ $company_name }}"><br>
        @endif
        <h1>Invoice Ready</h1>
        <p>{{ $company_name }}</p>
    </div>

    {{-- Body --}}
    <div class="body">
        <div class="greeting">
            Hello, <strong>{{ $invoice->billing_name ?? $invoice->user?->name ?? 'Valued Customer' }}</strong> 👋
        </div>

        <p style="margin-bottom:20px;">
            Please find your invoice <strong>#{{ $invoice->invoice_number }}</strong> attached to this email.
            Here's a quick summary:
        </p>

        {{-- Invoice Summary Box --}}
        <div class="invoice-box">
            <table>
                <tr>
                    <td class="label">Invoice Number</td>
                    <td class="value">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="label">Invoice Type</td>
                    <td class="value">{{ $type_label }}</td>
                </tr>
                <tr>
                    <td class="label">Issue Date</td>
                    <td class="value">{{ $invoice->issued_at?->format('M d, Y') ?? now()->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Due Date</td>
                    <td class="value">{{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value">
                        <span style="color:{{ $invoice->status === 'paid' ? '#059669' : '#d97706' }}; font-weight:700;">
                            {{ strtoupper($status_label) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="amount-big">{{ $currency_symbol }}{{ $total_formatted }}</div>
        <div class="amount-label">Total Amount Due</div>

        @if(!empty($payment_instructions))
        <hr class="divider">
        <div class="message-block">
            <strong>Payment Instructions:</strong><br>
            {!! nl2br(e($payment_instructions)) !!}
        </div>
        @endif

        <hr class="divider">

        @if($invoice->notes)
        <div class="message-block">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        <hr class="divider">
        @endif

        <p style="font-size:13px; color:#6b7280;">
            The full invoice PDF is attached. If you have any questions, please contact us at
            <a href="mailto:{{ $company_email ?? '' }}" style="color:{{ $primary_color ?? '#1a56db' }};">{{ $company_email }}</a>.
        </p>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <strong>{{ $company_name }}</strong>
        @if(!empty($footer_text))<br>{{ $footer_text }}@endif
        <br>Invoice #{{ $invoice->invoice_number }} · {{ now()->format('Y') }}
    </div>
</div>
</body>
</html>
