<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice_number }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .logo { font-size: 20px; font-weight: bold; color: #6366f1; }
        .invoice-info { text-align: right; }
        .invoice-info h2 { margin: 0; font-size: 24px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9fafb; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; }
        .footer { margin-top: 60px; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="logo">{{ $site_title }}</div>
        </div>
        <div class="invoice-info">
            <h2>{{ $invoice_number }}</h2>
            <p>{{ $date }}</p>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <strong>{{ __('invoices.bill_to') }}:</strong><br>
        {{ $user->name }}<br>
        {{ $user->email }}<br>
        @if($billing && isset($billing['company'])){{ $billing['company'] }}<br>@endif
        @if($billing && isset($billing['address'])){{ $billing['address'] }}<br>@endif
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('invoices.description') }}</th>
                <th>{{ __('invoices.quantity') }}</th>
                <th>{{ __('invoices.unit_price') }}</th>
                <th>{{ __('invoices.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $plan?->name ?? $payment->plan_id }}</td>
                <td>1</td>
                <td>{{ $payment->total_amount }} {{ $payment->currency }}</td>
                <td>{{ $payment->total_amount }} {{ $payment->currency }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align:right">{{ __('invoices.total') }}</td>
                <td>{{ $payment->total_amount }} {{ $payment->currency }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        {{ $site_title }} — {{ __('invoices.footer_text') }}
    </div>

    <script>window.print();</script>
</body>
</html>