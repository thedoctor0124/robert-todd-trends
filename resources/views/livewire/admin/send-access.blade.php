<div>
    <div class="mb-4">
        <h3 class="font-serif mb-1">Send free access link</h3>
        <p class="text-muted small mb-0">
            Email a secure link so the recipient can sign in or register and open their publication. Works for existing customers or new accounts.
        </p>
    </div>

    @if($sentClaimUrl)
        <div class="alert alert-success">
            <strong>Access link ready.</strong> Copy and share if the recipient does not receive the email:
            <div class="mt-2 small font-monospace text-break">{{ $sentClaimUrl }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <form wire:submit="sendInvite">
                    <div class="mb-4">
                        <label class="form-label small text-uppercase ls-wide">Recipient</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="mode-existing" value="existing" wire:model.live="recipientMode">
                                <label class="form-check-label" for="mode-existing">Existing customer</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="mode-new" value="new" wire:model.live="recipientMode">
                                <label class="form-check-label" for="mode-new">New customer</label>
                            </div>
                        </div>
                    </div>

                    @if($recipientMode === 'existing')
                        <div class="mb-3">
                            <label class="form-label small">Customer</label>
                            <select class="form-select" wire:model.live="userId">
                                <option value="">Select a user...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                                @endforeach
                            </select>
                            @error('userId') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label small">Full name</label>
                            <input type="text" class="form-control" wire:model="invitedName" placeholder="Jane Smith">
                            @error('invitedName') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Email address</label>
                            <input type="email" class="form-control" wire:model="email" placeholder="jane@company.com">
                            @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                            <div class="form-text">They will create a password when they open the link.</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small">Access type</label>
                        <select class="form-select" wire:model.live="accessType">
                            <option value="publication">Individual publication</option>
                            <option value="subscription">Full season subscription</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small">
                            {{ $accessType === 'publication' ? 'Publication' : 'Season' }}
                        </label>
                        <select class="form-select" wire:model="grantItemId">
                            <option value="0">Select...</option>
                            @if($accessType === 'publication')
                                @foreach($allPublications as $pub)
                                    <option value="{{ $pub->id }}">{{ $pub->title }} ({{ $pub->season->name }} {{ $pub->season->year }})</option>
                                @endforeach
                            @else
                                @foreach($allSeasons as $season)
                                    <option value="{{ $season->id }}">{{ $season->name }} ({{ $season->year }})</option>
                                @endforeach
                            @endif
                        </select>
                        @error('grantItemId') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="sendInvite">Send access link</span>
                        <span wire:loading wire:target="sendInvite">Sending...</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06);">
                <h6 class="text-uppercase ls-wide small mb-3">Recent invites</h6>
                @if($recentInvites->isEmpty())
                    <p class="text-muted small mb-0">No invites sent yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-minimal mb-0">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Item</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentInvites as $invite)
                                    <tr>
                                        <td class="small">{{ $invite->email }}</td>
                                        <td class="small">{{ $invite->itemTitle() }}</td>
                                        <td class="small">
                                            @if($invite->isRedeemed())
                                                <span class="text-success">Claimed</span>
                                            @elseif($invite->isExpired())
                                                <span class="text-danger">Expired</span>
                                            @else
                                                <span class="text-muted">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
