<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #383838; font-size: 12px; line-height: 1.45; }
        h1 { font-size: 24px; letter-spacing: 2px; margin: 0 0 20px; }
        h2 { font-size: 13px; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { text-align: left; background: #f5f3ef; }
        .muted { color: #777; }
        .right { text-align: right; }
        .block { margin-bottom: 28px; }
        .grid { width: 100%; margin-bottom: 28px; }
        .grid td { width: 50%; border: 0; padding: 0 20px 0 0; }
        .totals { width: 45%; margin-left: auto; }
        .totals td { border-bottom: 0; padding: 5px 0; }
        .total td { border-top: 2px solid #383838; padding-top: 9px; font-weight: bold; font-size: 14px; }
        .vat-warning { margin-top: 18px; padding: 10px; background: #fff5d6; border: 1px solid #e7c56f; font-size: 11px; }
    </style>
</head>
<body>
    <h1>VAT Invoice</h1>

    <table class="grid">
        <tr>
            <td>
                <h2>From</h2>
                <strong>{{ $company['name'] }}</strong><br>
                {{ $company['email'] }}<br>
                @if($company['vat_number'])
                    VAT No: {{ $company['vat_number'] }}
                @else
                    <span class="muted">VAT number not configured</span>
                @endif
            </td>
            <td>
                <h2>Invoice Details</h2>
                Invoice number: <strong>{{ $invoiceNumber }}</strong><br>
                Invoice date: {{ $invoiceDate->format('d M Y') }}<br>
                Transaction ID: {{ $transactionId ?: 'N/A' }}
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td>
                <h2>Bill To</h2>
                <strong>{{ $customerName }}</strong><br>
                {{ $customerEmail }}
            </td>
            <td>
                <h2>Delivery</h2>
                @if($deliveryRequired && $deliveryAddress)
                    {!! nl2br(e($deliveryAddress)) !!}
                @else
                    <span class="muted">Digital delivery only</span>
                @endif
            </td>
        </tr>
    </table>

    <div class="block">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Net</th>
                    <th class="right">VAT ({{ number_format($vatRate, 0) }}%)</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $item }}</strong><br>
                        <span class="muted">{{ $description }}</span>
                        @if($discountCode)
                            <br><span class="muted">Discount code: {{ $discountCode }}</span>
                        @endif
                    </td>
                    <td class="right">&pound;{{ number_format($net, 2) }}</td>
                    <td class="right">&pound;{{ number_format($vat, 2) }}</td>
                    <td class="right">&pound;{{ number_format($gross, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
            <td>Net amount</td>
            <td class="right">&pound;{{ number_format($net, 2) }}</td>
        </tr>
        <tr>
            <td>VAT</td>
            <td class="right">&pound;{{ number_format($vat, 2) }}</td>
        </tr>
        <tr class="total">
            <td>Total paid</td>
            <td class="right">&pound;{{ number_format($gross, 2) }}</td>
        </tr>
    </table>

    @unless($company['vat_number'])
        <div class="vat-warning">
            Internal note: add COMPANY_VAT_NUMBER to production .env so downloaded VAT invoices include the registered VAT number.
        </div>
    @endunless
</body>
</html>
