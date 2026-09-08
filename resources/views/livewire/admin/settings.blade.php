<div>
    <h3 class="font-serif mb-4">Email Settings</h3>

    <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06); max-width: 760px;">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h6 class="text-uppercase ls-wide small mb-2">Status</h6>
                <p class="text-muted small mb-0">
                    @if($mailConfigured)
                        Outgoing email is sent through <strong>{{ $mailHost }}:{{ $mailPort }}</strong>
                        as <strong>{{ $effectiveFromAddress }}</strong>.
                    @else
                        No SMTP credentials are stored, so the application falls back to the
                        <code>MAIL_*</code> values in <code>.env</code>. Order notifications and free-access
                        emails will not reach anyone while that is set to <code>log</code>.
                    @endif
                </p>
            </div>
            <span class="badge {{ $mailConfigured ? 'text-bg-success' : 'text-bg-secondary' }}">
                {{ $mailConfigured ? 'Configured' : 'Not configured' }}
            </span>
        </div>

        <details class="small">
            <summary class="text-muted" style="cursor: pointer;">How to get a Gmail app password</summary>
            <ol class="text-muted mt-2 mb-0 ps-3">
                <li>Sign in to the Google account that should send the mail.</li>
                <li>Turn on 2-Step Verification &mdash; app passwords are unavailable without it.</li>
                <li>Go to <strong>Google Account &rarr; Security &rarr; App passwords</strong> and create one.</li>
                <li>Paste the 16-character password below. Spaces are stripped automatically.</li>
            </ol>
        </details>
    </div>

    <div class="bg-white p-4 mb-4" style="border: 1px solid rgba(56,56,56,0.06); max-width: 760px;">
        <form wire:submit="save">
            <h6 class="text-uppercase ls-wide small mb-3">SMTP Server</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">Host</label>
                    <input type="text" class="form-control" wire:model="mailHost" placeholder="smtp.gmail.com">
                    @error('mailHost') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-uppercase ls-wide">Port</label>
                    <input type="number" class="form-control" wire:model="mailPort">
                    @error('mailPort') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-uppercase ls-wide">Security</label>
                    <select class="form-select" wire:model="mailEncryption">
                        <option value="tls">TLS (port 587)</option>
                        <option value="ssl">SSL (port 465)</option>
                    </select>
                    @error('mailEncryption') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-uppercase ls-wide">Username</label>
                <input type="text" class="form-control" wire:model="mailUsername" placeholder="you@gmail.com" autocomplete="off">
                <div class="form-text small">The full Google account address that will authenticate.</div>
                @error('mailUsername') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label small text-uppercase ls-wide">App Password</label>
                <input type="password" class="form-control" wire:model="mailPassword" autocomplete="new-password"
                       placeholder="{{ $passwordIsSet ? 'Stored — leave blank to keep it' : 'xxxx xxxx xxxx xxxx' }}">
                <div class="form-text small">
                    @if($passwordIsSet)
                        A password is stored (encrypted). Enter a new one only to replace it.
                    @else
                        Not your normal Google password &mdash; use a 16-character app password.
                    @endif
                </div>
                @error('mailPassword') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <h6 class="text-uppercase ls-wide small mb-3">Sent From</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">From Address</label>
                    <input type="email" class="form-control" wire:model="mailFromAddress" placeholder="{{ $mailUsername ?: 'you@gmail.com' }}">
                    <div class="form-text small">Leave blank to send as the username above.</div>
                    @error('mailFromAddress') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-uppercase ls-wide">From Name</label>
                    <input type="text" class="form-control" wire:model="mailFromName" placeholder="Robert Todd Trends">
                    @error('mailFromName') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>

            @if($mailFromAddress && $mailUsername && $mailFromAddress !== $mailUsername)
                <div class="alert alert-warning small py-2">
                    Gmail only accepts a From address matching the authenticated account or one of its
                    verified <strong>Send mail as</strong> aliases. Add
                    <strong>{{ $mailFromAddress }}</strong> under Gmail &rarr; Settings &rarr; Accounts and Import,
                    or sending will be rejected.
                </div>
            @endif

            <div class="d-flex gap-2 align-items-center">
                <button type="submit" class="btn btn-primary">Save Email Settings</button>
                @if($passwordIsSet)
                    <button type="button" wire:click="clearPassword"
                            wire:confirm="Clear the stored app password? Email sending will stop until a new one is saved."
                            class="btn btn-outline-danger">
                        Clear Stored Password
                    </button>
                @endif
                <span wire:loading wire:target="save" class="text-muted small">Saving&hellip;</span>
            </div>
        </form>
    </div>

    <div class="bg-white p-4" style="border: 1px solid rgba(56,56,56,0.06); max-width: 760px;">
        <h6 class="text-uppercase ls-wide small mb-2">Send a Test</h6>
        <p class="text-muted small mb-3">
            Sends a plain-text message using the saved settings. Save any changes first.
        </p>
        <div class="row g-3 align-items-start">
            <div class="col-md-8">
                <input type="email" class="form-control" wire:model="testRecipient">
                @error('testRecipient') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
            <div class="col-md-4">
                <button type="button" wire:click="sendTest" class="btn btn-outline-primary w-100">
                    <span wire:loading.remove wire:target="sendTest">Send Test Email</span>
                    <span wire:loading wire:target="sendTest">Sending&hellip;</span>
                </button>
            </div>
        </div>

        @if($testStatus)
            <div class="alert {{ $testStatus === 'success' ? 'alert-success' : 'alert-danger' }} small mt-3 mb-0">
                @if($testStatus === 'success')
                    {{ $testMessage }}
                @else
                    <strong>Test failed.</strong>
                    <div class="mt-1" style="word-break: break-word;">{{ $testMessage }}</div>
                    <div class="mt-2 mb-0">
                        A <em>Username and Password not accepted</em> response from Gmail usually means the
                        password is an ordinary account password rather than an app password, or that
                        2-Step Verification is not enabled on the account.
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
