<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentNo }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, "PingFang SC", "Noto Sans SC", "Microsoft YaHei", sans-serif; color: #18181b; margin: 0; background: #f4f4f5; }
        .page { max-width: 800px; margin: 40px auto; background: #fff; padding: 48px; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        h1 { margin: 0 0 4px; font-size: 24px; }
        .doc-no { color: #71717a; font-size: 13px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #18181b; padding-bottom: 24px; margin-bottom: 24px; }
        .muted { color: #71717a; font-size: 13px; line-height: 1.7; }
        table.items { width: 100%; border-collapse: collapse; margin: 24px 0; font-size: 14px; }
        table.items th { text-align: left; padding: 10px 12px; background: #f4f4f5; border-bottom: 1px solid #e4e4e7; font-weight: 600; }
        table.items td { padding: 12px; border-bottom: 1px solid #f4f4f5; }
        .total-row td { font-weight: 700; font-size: 15px; }
        .credit-note .page, .page.credit-note { outline: 2px dashed #dc2626; }
        .badge-cn { display: inline-block; background: #fef2f2; color: #dc2626; font-size: 12px; padding: 2px 10px; border-radius: 999px; border: 1px solid #fecaca; }
        .footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e4e4e7; color: #a1a1aa; font-size: 12px; text-align: center; }
        @media print { body { background: #fff; } .page { margin: 0; box-shadow: none; border-radius: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
<div class="page {{ $mode === 'credit_note' ? 'credit-note' : '' }}">
    <div class="head">
        <div>
            <h1>{{ $mode === 'credit_note' ? __('admin.credit_note') : __('admin.invoice') }}</h1>
            <p class="doc-no">{{ $documentNo }} · {{ now()->format('Y-m-d') }}</p>
            @if($mode === 'credit_note')<p class="badge-cn" style="margin-top:8px">{{ __('admin.credit_note_badge') }}</p>@endif
        </div>
        <div class="muted" style="text-align:right">
            <strong style="color:#18181b;font-size:15px">{{ $company['name'] }}</strong><br>
            {{ $company['email'] }}<br>
            {{ $company['url'] }}
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:24px">
        <div class="muted">
            <strong style="color:#18181b">{{ __('admin.billed_to') }}</strong><br>
            {{ $payment->name }}<br>
            {{ $payment->email }}
        </div>
        <div class="muted" style="text-align:right">
            <strong style="color:#18181b">{{ __('admin.payment_details') }}</strong><br>
            {{ __('admin.payment_id') }}: {{ $payment->payment_id }}<br>
            {{ __('admin.processor') }}: {{ $payment->payment_processor }}<br>
            {{ __('admin.datetime') }}: {{ $payment->datetime?->format('Y-m-d H:i') }}
        </div>
    </div>

    <table class="items">
        <thead>
            <tr><th>{{ __('admin.item') }}</th><th>{{ __('admin.type') }}</th><th>{{ __('admin.frequency') }}</th><th style="text-align:right">{{ __('admin.amount') }}</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->type === 'plan' ? __('admin.plan_purchase') : ($payment->type === 'code' ? __('admin.code_redeem') : $payment->type) }}</td>
                <td>{{ $payment->type }}</td>
                <td>{{ $payment->frequency ?: '-' }}</td>
                <td style="text-align:right">
                    {{ $mode === 'credit_note' ? '-' : '' }}{{ number_format((float) $payment->total_amount, 2) }} {{ $payment->currency }}
                </td>
            </tr>
            @if((float) $payment->discount_amount > 0)
            <tr>
                <td>{{ __('admin.discount') }}</td>
                <td>-</td>
                <td>-</td>
                <td style="text-align:right">-{{ number_format((float) $payment->discount_amount, 2) }} {{ $payment->currency }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3">{{ __('admin.total') }}</td>
                <td style="text-align:right">{{ $mode === 'credit_note' ? '-' : '' }}{{ number_format((float) $payment->total_amount, 2) }} {{ $payment->currency }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted">{{ __('admin.invoice_thanks') }}</p>

    <div class="footer">{{ $company['name'] }} · {{ $documentNo }}</div>
</div>
<div class="no-print" style="text-align:center;margin-bottom:40px">
    <button onclick="window.print()" style="padding:10px 28px;border:none;border-radius:10px;background:#2563eb;color:#fff;font-size:14px;cursor:pointer">{{ __('admin.print_document') }}</button>
</div>
</body>
</html>
