<div>
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.users.index') }}" class="text-muted text-decoration-none me-3">&larr;</a>
        <div>
            <h3 class="font-serif mb-0">{{ $user->name }}</h3>
            <span class="text-muted small">{{ $user->email }}</span>
        </div>
        <div class="ms-auto">
            <button wire:click="toggleAdmin" class="btn btn-sm {{ $user->is_admin ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                {{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
            </button>
        </div>
    </div>

    <div class="row g-4">
        {{-- Grant Access --}}
        <div class="col-md-6">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h6 class="text-uppercase ls-wide small mb-3">Grant Free Access</h6>

                @if($granted)
                    <div class="alert alert-success small py-2">Access granted and email sent to user.</div>
                @endif

                <form wire:submit="grantAccess">
                    <div class="mb-3">
                        <label class="form-label small">Access Type</label>
                        <select class="form-select" wire:model.live="grantType">
                            <option value="publication">Individual Publication</option>
                            <option value="subscription">Season Subscription</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">
                            {{ $grantType === 'publication' ? 'Publication' : 'Season' }}
                        </label>
                        <select class="form-select" wire:model="grantItemId">
                            <option value="0">Select...</option>
                            @if($grantType === 'publication')
                                @foreach($allPublications as $pub)
                                    <option value="{{ $pub->id }}">{{ $pub->title }} ({{ $pub->season->name }})</option>
                                @endforeach
                            @else
                                @foreach($allSeasons as $season)
                                    <option value="{{ $season->id }}">{{ $season->name }} ({{ $season->year }})</option>
                                @endforeach
                            @endif
                        </select>
                        @error('grantItemId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-secondary">Grant Free Access</button>
                </form>
            </div>
        </div>

        {{-- User Info --}}
        <div class="col-md-6">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h6 class="text-uppercase ls-wide small mb-3">User Details</h6>
                <table class="table table-sm table-minimal">
                    <tr><td class="text-muted">Joined</td><td>{{ $user->created_at->format('d M Y') }}</td></tr>
                    <tr><td class="text-muted">Google Linked</td><td>{{ $user->google_id ? 'Yes' : 'No' }}</td></tr>
                    <tr><td class="text-muted">Admin</td><td>{{ $user->is_admin ? 'Yes' : 'No' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Current Access --}}
    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h6 class="text-uppercase ls-wide small mb-3">Subscriptions</h6>
                @if($subscriptions->count())
                    <table class="table table-minimal table-sm">
                        <thead><tr><th>Season</th><th>Type</th><th>Paid</th><th></th></tr></thead>
                        <tbody>
                            @foreach($subscriptions as $sub)
                                <tr>
                                    <td>{{ $sub->season->name }} ({{ $sub->season->year }})</td>
                                    <td>@if($sub->is_free) <span class="badge-gold">Free</span> @else Paid @endif</td>
                                    <td>&pound;{{ number_format($sub->amount_paid, 2) }}</td>
                                    <td>
                                        <button wire:click="revokeSubscription({{ $sub->id }})" wire:confirm="Revoke this subscription?" class="btn btn-sm btn-outline-danger">Revoke</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted small mb-0">No subscriptions.</p>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h6 class="text-uppercase ls-wide small mb-3">Purchases</h6>
                @if($purchases->count())
                    <table class="table table-minimal table-sm">
                        <thead><tr><th>Publication</th><th>Type</th><th>Paid</th><th></th></tr></thead>
                        <tbody>
                            @foreach($purchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->publication->title }}</td>
                                    <td>@if($purchase->is_free) <span class="badge-gold">Free</span> @else Paid @endif</td>
                                    <td>&pound;{{ number_format($purchase->amount_paid, 2) }}</td>
                                    <td>
                                        <button wire:click="revokePublication({{ $purchase->id }})" wire:confirm="Revoke this purchase?" class="btn btn-sm btn-outline-danger">Revoke</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted small mb-0">No purchases.</p>
                @endif
            </div>
        </div>
    </div>
</div>
