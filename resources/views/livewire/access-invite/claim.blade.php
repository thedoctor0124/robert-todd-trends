<div>
    <div class="auth-card">
        <h2 class="font-serif mb-1">Claim your free access</h2>
        <p class="text-muted small mb-4">
            You've been invited to view
            <strong>{{ $invite->itemTitle() }}</strong>
            on {{ config('app.name') }}.
        </p>

        @if($errorMessage)
            <div class="alert alert-warning small py-2">{{ $errorMessage }}</div>
            <p class="small text-muted mb-0">
                <a href="{{ route('login') }}">Sign in</a> or contact support if you need help.
            </p>
        @else
            <div class="highlight-box mb-4 p-3" style="background: #faf8f5; border-left: 3px solid #c9a96e;">
                <div class="small text-muted text-uppercase ls-wide mb-1">Sent to</div>
                <div>{{ $invite->email }}</div>
                <div class="small text-muted mt-2">Valid until {{ $invite->expires_at->format('j F Y') }}</div>
            </div>

            @auth
                @if(strtolower(auth()->user()->email) === strtolower($invite->email))
                    <p class="small text-muted mb-3">Signed in as {{ auth()->user()->name }}. Click below to open your report.</p>
                    <button type="button" class="btn btn-primary w-100" wire:click="claimAsCurrentUser">
                        Open my report
                    </button>
                @else
                    <div class="alert alert-warning small py-2">
                        You are signed in as {{ auth()->user()->email }}. This link is for {{ $invite->email }}.
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100">Sign out and continue</button>
                    </form>
                @endif
            @else
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $authMode === 'login' ? 'active' : '' }}" wire:click="$set('authMode', 'login')">
                            Sign in
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $authMode === 'register' ? 'active' : '' }}" wire:click="$set('authMode', 'register')">
                            Create account
                        </button>
                    </li>
                </ul>

                @if($existingAccount?->google_id)
                    <a href="{{ route('auth.google') }}" class="btn google-btn w-100 mb-4">
                        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 01-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                            <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
                            <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                            <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                        </svg>
                        Continue with Google
                    </a>
                    <div class="d-flex align-items-center mb-4">
                        <hr class="flex-grow-1">
                        <span class="px-3 text-muted small">or use email</span>
                        <hr class="flex-grow-1">
                    </div>
                @endif

                @if($authMode === 'login')
                    <form wire:submit="login">
                        <div class="mb-3">
                            <label for="claim-email" class="form-label small text-uppercase ls-wide">Email</label>
                            <input type="email" class="form-control" id="claim-email" wire:model="email" readonly>
                        </div>
                        <div class="mb-4">
                            <label for="claim-password" class="form-label small text-uppercase ls-wide">Password</label>
                            <input type="password" class="form-control" id="claim-password" wire:model="password" required autofocus>
                            @error('email') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                            Sign in &amp; open report
                        </button>
                    </form>
                @else
                    <form wire:submit="register">
                        <div class="mb-3">
                            <label for="claim-name" class="form-label small text-uppercase ls-wide">Full name</label>
                            <input type="text" class="form-control" id="claim-name" wire:model="name" required>
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="claim-reg-email" class="form-label small text-uppercase ls-wide">Email</label>
                            <input type="email" class="form-control" id="claim-reg-email" wire:model="email" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="claim-reg-password" class="form-label small text-uppercase ls-wide">Password</label>
                            <input type="password" class="form-control" id="claim-reg-password" wire:model="password" required>
                            @error('password') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="claim-reg-password-confirm" class="form-label small text-uppercase ls-wide">Confirm password</label>
                            <input type="password" class="form-control" id="claim-reg-password-confirm" wire:model="password_confirmation" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                            Create account &amp; open report
                        </button>
                    </form>
                @endif
            @endauth
        @endif
    </div>
</div>
