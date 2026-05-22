<div>
    <h3 class="font-serif mb-4">Dashboard</h3>

    <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
            <div>
                <h6 class="text-uppercase ls-wide small mb-2">Subscription Access</h6>
                <p class="text-muted small mb-0">
                    @if($subscriptionAccessEnabled)
                        Active subscriptions currently unlock season publications.
                    @else
                        Subscription access is disabled. Individual publication purchases still work.
                    @endif
                </p>
            </div>
            <div class="text-md-end">
                <span class="badge {{ $subscriptionAccessEnabled ? 'text-bg-success' : 'text-bg-secondary' }} mb-2">
                    {{ $subscriptionAccessEnabled ? 'Enabled' : 'Disabled' }}
                </span>
                <div>
                    @if($subscriptionAccessEnabled)
                        <button type="button" wire:click="disableSubscriptionAccess" class="btn btn-outline-primary btn-sm">
                            Disable Subscription Access
                        </button>
                    @else
                        <button type="button" wire:click="enableSubscriptionAccess" class="btn btn-primary btn-sm">
                            Enable Subscription Access
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06);">
        <h6 class="text-uppercase ls-wide small mb-2">Order Notifications</h6>
        <p class="text-muted small mb-3">
            New order emails are sent to this address with item, invoice, payment transaction ID and delivery details.
        </p>
        <form wire:submit="saveOrderNotificationEmail" class="row g-3 align-items-start">
            <div class="col-md-8">
                <input type="email" class="form-control" wire:model="orderNotificationEmail">
                @error('orderNotificationEmail') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Save Email</button>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $totalUsers }}</div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $totalSeasons }}</div>
                <div class="stat-label">Seasons</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $totalPurchases + $totalSubscriptions }}</div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">&pound;{{ number_format($revenue, 2) }}</div>
                <div class="stat-label">Revenue</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h6 class="text-uppercase ls-wide small mb-3">Recent Purchases</h6>
                @if($recentPurchases->count())
                    <table class="table table-minimal table-sm">
                        <thead>
                            <tr><th>User</th><th>Publication</th><th>Amount</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentPurchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->user->name }}</td>
                                    <td>{{ $purchase->publication->title }}</td>
                                    <td>@if($purchase->is_free) <span class="badge-gold">Free</span> @else &pound;{{ number_format($purchase->amount_paid, 2) }} @endif</td>
                                    <td>{{ $purchase->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted small mb-0">No purchases yet.</p>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h6 class="text-uppercase ls-wide small mb-3">Recent Subscriptions</h6>
                @if($recentSubscriptions->count())
                    <table class="table table-minimal table-sm">
                        <thead>
                            <tr><th>User</th><th>Season</th><th>Amount</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentSubscriptions as $sub)
                                <tr>
                                    <td>{{ $sub->user->name }}</td>
                                    <td>{{ $sub->season->name }}</td>
                                    <td>@if($sub->is_free) <span class="badge-gold">Free</span> @else &pound;{{ number_format($sub->amount_paid, 2) }} @endif</td>
                                    <td>{{ $sub->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted small mb-0">No subscriptions yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
