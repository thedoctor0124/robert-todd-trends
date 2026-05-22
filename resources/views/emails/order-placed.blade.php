<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Gill Sans', 'Gill Sans MT', 'Helvetica Neue', Arial, sans-serif; color: rgb(56, 56, 56); background-color: #faf8f5; margin: 0; padding: 0; }
        .container { max-width: 680px; margin: 0 auto; padding: 32px 20px; }
        .card { background: #fff; border: 1px solid rgba(56, 56, 56, 0.08); padding: 32px; }
        .logo { font-family: Georgia, serif; font-size: 22px; letter-spacing: 0.1em; margin-bottom: 22px; }
        h1 { font-family: Georgia, serif; font-size: 22px; font-weight: 400; letter-spacing: 0.08em; margin: 0 0 18px; }
        h2 { font-size: 13px; letter-spacing: 0.1em; text-transform: uppercase; margin: 26px 0 8px; }
        p, td { font-size: 14px; line-height: 1.55; color: #6b6b6b; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 0; border-bottom: 1px solid #eee; vertical-align: top; }
        td:first-child { width: 36%; color: #383838; font-weight: 600; }
        .urgent { background: #102a43; color: #fff; padding: 10px 14px; display: inline-block; letter-spacing: 0.08em; text-transform: uppercase; font-size: 12px; }
        .digital { background: #6b6b6b; color: #fff; padding: 10px 14px; display: inline-block; letter-spacing: 0.08em; text-transform: uppercase; font-size: 12px; }
    </style>
</head>
<body>
    @php
        $isPurchase = $orderType === 'purchase';
        $itemName = $isPurchase ? $order->publication->title : $order->season->name.' '.$order->season->year;
        $invoiceNumber = 'RTT-'.$order->created_at->format('Y').'-'.($isPurchase ? 'P' : 'S').str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
        $deliveryAddress = collect([
            $order->delivery_name,
            $order->delivery_address_line_1,
            $order->delivery_address_line_2,
            $order->delivery_city,
            $order->delivery_county,
            $order->delivery_postcode,
            $order->delivery_country,
        ])->filter()->implode('<br>');
    @endphp

    <div class="container">
        <div class="card">
            <div class="logo">{{ config('app.name') }}</div>
            <h1>New Order Placed</h1>

            @if($order->delivery_required)
                <p><span class="urgent">Printed copy needs posting</span></p>
            @else
                <p><span class="digital">Digital only</span></p>
            @endif

            <h2>Order</h2>
            <table>
                <tr><td>Invoice number</td><td>{{ $invoiceNumber }}</td></tr>
                <tr><td>Item</td><td>{{ $itemName }}</td></tr>
                <tr><td>Amount paid</td><td>&pound;{{ number_format($order->amount_paid, 2) }}</td></tr>
                <tr><td>Transaction ID</td><td>{{ $order->square_payment_id ?: 'N/A' }}</td></tr>
                <tr><td>Date</td><td>{{ $order->created_at->format('d M Y H:i') }}</td></tr>
            </table>

            <h2>Customer</h2>
            <table>
                <tr><td>Name</td><td>{{ $order->user->name }}</td></tr>
                <tr><td>Email</td><td>{{ $order->user->email }}</td></tr>
            </table>

            @if($order->delivery_required)
                <h2>Delivery Address</h2>
                <p>{!! $deliveryAddress !!}</p>
                @if($order->delivery_phone)
                    <p><strong>Phone:</strong> {{ $order->delivery_phone }}</p>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
