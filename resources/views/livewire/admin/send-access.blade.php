<div>
    <div class="d-flex align-items-start mb-4">
        <div>
            <h3 class="font-serif mb-1">Send free access link</h3>
            <p class="text-muted small mb-0">
                Email a secure link so the recipient can sign in or register and open their publication. Works for existing customers or new accounts.
            </p>
        </div>
        <a href="{{ route('admin.send-access.bulk') }}" class="btn btn-sm btn-outline-primary ms-auto flex-shrink-0">
            Bulk send
        </a>
    </div>

    @if($feedbackStatus)
        <div class="alert {{ $feedbackStatus === 'success' ? 'alert-success' : 'alert-danger' }}">
            @if($feedbackStatus === 'success')
                {{ $feedbackMessage }}
            @else
                <strong>Email not sent.</strong>
                <div class="mt-1 small" style="word-break: break-word;">{{ $feedbackMessage }}</div>
                <div class="mt-2 small mb-0">
                    Check <a href="{{ route('admin.settings') }}" class="alert-link">Settings &rarr; Email</a>,
                    then use <strong>Resend</strong>. The access link below still works if you share it directly.
                </div>
            @endif
        </div>
    @endif

    @if($sentClaimUrl)
        <div class="alert alert-success">
            <strong>Access link ready.</strong> Copy and share if the recipient does not receive the email:
            <div class="mt-2 small font-monospace text-break">{{ $sentClaimUrl }}</div>
        </div>
    @endif

    @if($undeliveredInvites->isNotEmpty())
        <div class="alert alert-danger">
            <strong>{{ $undeliveredInvites->count() }}
                {{ Str::plural('invite', $undeliveredInvites->count()) }} could not be emailed.</strong>
            These links are still valid but the recipient was never told. Fix sending under
            <a href="{{ route('admin.settings') }}" class="alert-link">Settings &rarr; Email</a>, then resend.
            <ul class="mb-0 mt-2 small">
                @foreach($undeliveredInvites as $failed)
                    <li>
                        {{ $failed->email }} &mdash; {{ $failed->itemTitle() }}
                        <span class="text-muted">(failed {{ $failed->send_failed_at->diffForHumans() }})</span>
                    </li>
                @endforeach
            </ul>
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
                        <label class="form-label small">Company</label>
                        <select class="form-select" wire:model.live="company">
                            <option value="" @selected($company === '')>{{ $recipientMode === 'existing' ? 'All companies' : 'No company' }}</option>
                            @foreach($companies as $companyName)
                                <option value="{{ $companyName }}" @selected($company === $companyName)>{{ $companyName }}</option>
                            @endforeach
                            @if($recipientMode === 'existing' && $missingCompanyCount)
                                <option value="__none" @selected($company === '__none')>No company set ({{ $missingCompanyCount }})</option>
                            @endif
                            <option value="__new" @selected($company === '__new')>+ Add a new company&hellip;</option>
                        </select>
                        @error('company') <span class="text-danger small">{{ $message }}</span> @enderror

                        @if($company === '__new')
                            <input type="text" class="form-control mt-2" wire:model="newCompany"
                                   placeholder="New company name">
                            @error('newCompany') <span class="text-danger small">{{ $message }}</span> @enderror
                        @endif

                        <div class="form-text">
                            @if($recipientMode === 'existing')
                                @if($company === '__none')
                                    Showing customers with no company. Pick one, then choose their company above to assign it.
                                @elseif($company === '__new')
                                    Type the company name. It will be saved to whoever you pick below.
                                @elseif($company === '')
                                    Choose a company to narrow the customer list below.
                                @else
                                    The customer list is narrowed to this company, and it will be saved to whoever you pick.
                                @endif
                            @else
                                Optional. Saved to their account when they claim the link.
                            @endif
                        </div>
                    </div>

                        <div class="mb-3">
                            <label class="form-label small">Customer</label>
                            <select class="form-select" wire:model.live="userId">
                                <option value="">Select a user...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected($userId === $user->id)>
                                        {{ $user->name }} — {{ $user->email }}@if($user->company) ({{ $user->company }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('userId') <span class="text-danger small">{{ $message }}</span> @enderror
                            @if($users->isEmpty())
                                <div class="form-text text-danger">No customers match this company.</div>
                            @endif
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
                    <div class="mb-3">
                        <label class="form-label small">Company</label>
                        <select class="form-select" wire:model.live="company">
                            <option value="" @selected($company === '')>{{ $recipientMode === 'existing' ? 'All companies' : 'No company' }}</option>
                            @foreach($companies as $companyName)
                                <option value="{{ $companyName }}" @selected($company === $companyName)>{{ $companyName }}</option>
                            @endforeach
                            @if($recipientMode === 'existing' && $missingCompanyCount)
                                <option value="__none" @selected($company === '__none')>No company set ({{ $missingCompanyCount }})</option>
                            @endif
                            <option value="__new" @selected($company === '__new')>+ Add a new company&hellip;</option>
                        </select>
                        @error('company') <span class="text-danger small">{{ $message }}</span> @enderror

                        @if($company === '__new')
                            <input type="text" class="form-control mt-2" wire:model="newCompany"
                                   placeholder="New company name">
                            @error('newCompany') <span class="text-danger small">{{ $message }}</span> @enderror
                        @endif

                        <div class="form-text">
                            @if($recipientMode === 'existing')
                                @if($company === '__none')
                                    Showing customers with no company. Pick one, then choose their company above to assign it.
                                @elseif($company === '__new')
                                    Type the company name. It will be saved to whoever you pick below.
                                @elseif($company === '')
                                    Choose a company to narrow the customer list below.
                                @else
                                    The customer list is narrowed to this company, and it will be saved to whoever you pick.
                                @endif
                            @else
                                Optional. Saved to their account when they claim the link.
                            @endif
                        </div>
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
    </div>

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
                            <th>Delivery</th>
                            <th></th>
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
                                <td class="small">
                                    @if($invite->deliveryFailed())
                                        <span class="text-danger" title="{{ $invite->send_error }}">Failed</span>
                                    @elseif($invite->wasDelivered())
                                        <span class="text-success">Sent</span>
                                    @else
                                        <span class="text-muted" title="Sent before delivery was tracked">Unknown</span>
                                    @endif
                                </td>
                                <td class="small text-end">
                                    @if($invite->isValid())
                                        <button type="button"
                                                wire:click="resendInvite({{ $invite->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="resendInvite({{ $invite->id }})"
                                                class="btn btn-sm btn-outline-primary py-0 px-2">
                                            <span wire:loading.remove wire:target="resendInvite({{ $invite->id }})">Resend</span>
                                            <span wire:loading wire:target="resendInvite({{ $invite->id }})">&hellip;</span>
                                        </button>
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
