<div>
    <h3 class="font-serif mb-4">Orders</h3>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <div class="mb-3 d-flex gap-2">
            <button wire:click="$set('filter', 'all')" class="btn btn-sm {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All</button>
            <button wire:click="$set('filter', 'purchases')" class="btn btn-sm {{ $filter === 'purchases' ? 'btn-primary' : 'btn-outline-primary' }}">Purchases</button>
            <button wire:click="$set('filter', 'subscriptions')" class="btn btn-sm {{ $filter === 'subscriptions' ? 'btn-primary' : 'btn-outline-primary' }}">Subscriptions</button>
        </div>

        <table class="table table-minimal">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>User</th>
                    <th>Item</th>
                    <th>Amount</th>
                    <th>Delivery</th>
                    <th>Discount</th>
                    <th>Payment ID</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><span class="badge-{{ $order->type === 'Purchase' ? 'navy' : 'gold' }}">{{ $order->type }}</span></td>
                        <td>
                            <strong>{{ $order->user_name }}</strong>
                            <div class="text-muted small">{{ $order->user_email }}</div>
                        </td>
                        <td>{{ $order->item }}</td>
                        <td>
                            @if($order->is_free)
                                <span class="badge-gold">Free Pass</span>
                            @else
                                &pound;{{ number_format($order->amount, 2) }}
                            @endif
                        </td>
                        <td>
                            @if($order->delivery_required)
                                <strong>{{ $order->delivery_name }}</strong>
                                <div class="text-muted small">{{ $order->delivery_address }}</div>
                                @if($order->delivery_phone)
                                    <div class="text-muted small">{{ $order->delivery_phone }}</div>
                                @endif
                            @else
                                <span class="text-muted small">Digital only</span>
                            @endif
                        </td>
                        <td>{{ $order->discount ?? '—' }}</td>
                        <td class="small text-muted">{{ $order->payment_id ? Str::limit($order->payment_id, 20) : '—' }}</td>
                        <td>{{ $order->date->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
