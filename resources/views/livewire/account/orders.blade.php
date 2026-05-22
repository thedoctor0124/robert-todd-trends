<div>
    <section class="hero" style="padding: 3rem 0;">
        <div class="hero-content">
            <div class="container">
                <h1 class="h3 mb-1">Orders</h1>
                <p class="lead small mb-0">View your purchases and download VAT invoices.</p>
            </div>
        </div>
    </section>

    <section class="section-sm">
        <div class="container">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h4 class="font-serif mb-4">Order History</h4>

                <div class="table-responsive">
                    <table class="table table-minimal align-middle">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Item</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Transaction ID</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchases as $purchase)
                                <tr>
                                    <td>RTT-{{ $purchase->created_at->format('Y') }}-P{{ str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        <strong>{{ $purchase->publication->title }}</strong>
                                        <div class="text-muted small">Publication</div>
                                    </td>
                                    <td>{{ $purchase->created_at->format('d M Y') }}</td>
                                    <td>&pound;{{ number_format($purchase->amount_paid, 2) }}</td>
                                    <td class="small text-muted">{{ $purchase->square_payment_id ?: 'N/A' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('orders.invoice.purchase', $purchase) }}" class="btn btn-sm btn-outline-primary">Download Invoice</a>
                                    </td>
                                </tr>
                            @endforeach

                            @foreach($subscriptions as $subscription)
                                <tr>
                                    <td>RTT-{{ $subscription->created_at->format('Y') }}-S{{ str_pad((string) $subscription->id, 6, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        <strong>{{ $subscription->season->name }} {{ $subscription->season->year }}</strong>
                                        <div class="text-muted small">Season subscription</div>
                                    </td>
                                    <td>{{ $subscription->created_at->format('d M Y') }}</td>
                                    <td>&pound;{{ number_format($subscription->amount_paid, 2) }}</td>
                                    <td class="small text-muted">{{ $subscription->square_payment_id ?: 'N/A' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('orders.invoice.subscription', $subscription) }}" class="btn btn-sm btn-outline-primary">Download Invoice</a>
                                    </td>
                                </tr>
                            @endforeach

                            @if($purchases->isEmpty() && $subscriptions->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No orders yet.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
