<div
    id="checkout-component"
    data-app-id="{{ config('services.square.application_id') }}"
    data-location-id="{{ config('services.square.location_id') }}"
    data-environment="{{ config('services.square.environment') }}"
    data-square-src="{{ config('services.square.environment') === 'production' ? 'https://web.squarecdn.com/v1/square.js' : 'https://sandbox.web.squarecdn.com/v1/square.js' }}"
>
    <section class="section-sm">
        <div class="container">
            <div class="row g-5 justify-content-center">
                <div class="col-md-7">
                    <h2 class="font-serif mb-4">Checkout</h2>

                    @if($paymentError)
                        <div class="alert alert-danger">{{ $paymentError }}</div>
                    @endif

                    {{-- Order Summary --}}
                    <div class="checkout-summary mb-4">
                        <h6 class="text-uppercase ls-wide small mb-3">Order Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                @if($type === 'publication')
                                    <strong>{{ $item->title }}</strong>
                                    <div class="text-muted small">Individual publication</div>
                                @else
                                    <strong>{{ $item->name }} ({{ $item->year }})</strong>
                                    <div class="text-muted small">Season subscription — all publications</div>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="@if($discountAmount) text-decoration-line-through text-muted @endif">
                                    &pound;{{ number_format($this->getOriginalPrice(), 2) }}
                                </span>
                            </div>
                        </div>

                        @if($discountAmount)
                            <div class="d-flex justify-content-between text-success small mb-2">
                                <span>Discount ({{ $appliedDiscount->code }})</span>
                                <span>-&pound;{{ number_format($discountAmount, 2) }}</span>
                            </div>
                        @endif

                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <span class="summary-total">&pound;{{ number_format($this->getFinalPrice(), 2) }}</span>
                        </div>

                        <div class="text-muted small mt-3 mb-0">
                            @if($this->checkoutIncludesPrintedCopy())
                                Your order includes a printed magazine. During the first two weeks of a new print run, printed magazines may ship with a slight delay — your digital flipbook access is available immediately.
                            @else
                                This order is fully digital — your flipbook access is available immediately after payment. No printed magazine will be shipped.
                            @endif
                        </div>
                    </div>

                    @guest
                        <div class="checkout-summary mb-4">
                            <h6 class="text-uppercase ls-wide small mb-3">Your Account</h6>
                            <p class="text-muted small mb-3">
                                Sign in or create an account to complete checkout. Your publication access will be saved to this account.
                            </p>

                            <a href="{{ route('auth.google', ['popup' => 1]) }}" class="btn google-btn w-100 mb-4" data-checkout-google-popup>
                                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 01-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                                    <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
                                    <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                                    <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
                                </svg>
                                Continue with Google
                            </a>

                            <div class="btn-group w-100 mb-4" role="group" aria-label="Checkout account mode">
                                <button type="button" wire:click="$set('auth_mode', 'login')" class="btn {{ $auth_mode === 'login' ? 'btn-primary' : 'btn-outline-primary' }}">Sign In</button>
                                <button type="button" wire:click="$set('auth_mode', 'register')" class="btn {{ $auth_mode === 'register' ? 'btn-primary' : 'btn-outline-primary' }}">Create Account</button>
                            </div>

                            @if($auth_mode === 'login')
                                <form wire:submit="checkoutLogin">
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Email</label>
                                        <input type="email" class="form-control" wire:model="login_email" autocomplete="email">
                                        @error('login_email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Password</label>
                                        <input type="password" class="form-control" wire:model="login_password" autocomplete="current-password">
                                        @error('login_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="login_remember" wire:model="login_remember">
                                        <label class="form-check-label small" for="login_remember">Remember me</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Continue to Payment</button>
                                </form>
                            @else
                                <form wire:submit="checkoutRegister">
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Full name</label>
                                        <input type="text" class="form-control" wire:model="register_name" autocomplete="name">
                                        @error('register_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Email</label>
                                        <input type="email" class="form-control" wire:model="register_email" autocomplete="email">
                                        @error('register_email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Password</label>
                                        <input type="password" class="form-control" wire:model="register_password" autocomplete="new-password">
                                        @error('register_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-uppercase ls-wide">Confirm password</label>
                                        <input type="password" class="form-control" wire:model="register_password_confirmation" autocomplete="new-password">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Create Account & Continue</button>
                                </form>
                            @endif
                        </div>
                    @endguest

                    @auth
                    @if($this->checkoutIncludesPrintedCopy())
                        <div class="checkout-summary mb-4">
                            <h6 class="text-uppercase ls-wide small mb-3">Delivery Address</h6>
                            <p class="text-muted small mb-3">
                                We need a delivery address because this order includes a printed magazine.
                                Your digital access will still be available immediately after payment.
                            </p>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase ls-wide">Recipient name</label>
                                <input type="text" class="form-control" wire:model="delivery_name" autocomplete="shipping name">
                                @error('delivery_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase ls-wide">Address line 1</label>
                                <input type="text" class="form-control" wire:model="delivery_address_line_1" autocomplete="shipping address-line1">
                                @error('delivery_address_line_1') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase ls-wide">Address line 2 <span class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control" wire:model="delivery_address_line_2" autocomplete="shipping address-line2">
                                @error('delivery_address_line_2') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase ls-wide">Town / City</label>
                                    <input type="text" class="form-control" wire:model="delivery_city" autocomplete="shipping address-level2">
                                    @error('delivery_city') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase ls-wide">County <span class="text-muted">(optional)</span></label>
                                    <input type="text" class="form-control" wire:model="delivery_county" autocomplete="shipping address-level1">
                                    @error('delivery_county') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase ls-wide">Postcode</label>
                                    <input type="text" class="form-control" wire:model="delivery_postcode" autocomplete="shipping postal-code">
                                    @error('delivery_postcode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase ls-wide">Country</label>
                                    <input type="text" class="form-control" wire:model="delivery_country" autocomplete="shipping country-name">
                                    @error('delivery_country') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label small text-uppercase ls-wide">Phone number <span class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control" wire:model="delivery_phone" autocomplete="shipping tel">
                                @error('delivery_phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Discount Code --}}
                    <div class="mb-4">
                        <label class="form-label small text-uppercase ls-wide">Discount Code</label>
                        @if($appliedDiscount)
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge-gold px-3 py-2">{{ $appliedDiscount->code }}</span>
                                <button wire:click="removeDiscount" class="btn btn-sm btn-link text-danger">Remove</button>
                            </div>
                        @else
                            <div class="input-group">
                                <input type="text" class="form-control" wire:model="discountCode" placeholder="Enter code" style="text-transform: uppercase;">
                                <button wire:click="applyDiscount" class="btn btn-outline-primary">Apply</button>
                            </div>
                            @if($discountError)
                                <div class="text-danger small mt-1">{{ $discountError }}</div>
                            @endif
                        @endif
                    </div>

                    {{-- Payment Form - wire:ignore keeps Square iframe alive across Livewire re-renders --}}
                    <div class="mb-4">
                        <h6 class="text-uppercase ls-wide small mb-3">Payment Details</h6>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms_accepted" wire:model="terms_accepted">
                            <label class="form-check-label small" for="terms_accepted">
                                I agree to the
                                <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms & Conditions</a>,
                                including online-only digital access, no PDF file delivery, and UK-only delivery for printed copies.
                            </label>
                            @error('terms_accepted') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div wire:ignore>
                            <div id="card-container" style="min-height: 92px;">
                                <div class="text-muted small py-3">
                                    Loading secure card form...
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-1 d-none" data-card-retry>Retry</button>
                                </div>
                            </div>
                        </div>
                        <div id="card-errors" class="text-danger small mt-2" style="display:none;"></div>
                        <button id="card-button" type="button" class="btn btn-primary w-100 mt-3" disabled>
                            Loading secure card form...
                        </button>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    @push('head')
        <link rel="preconnect" href="{{ config('services.square.environment') === 'production' ? 'https://web.squarecdn.com' : 'https://sandbox.web.squarecdn.com' }}">
    @endpush

    @script
    <script>
        const el = document.getElementById('checkout-component');
        const appId = el.dataset.appId;
        const locationId = el.dataset.locationId;
        const squareSrc = el.dataset.squareSrc;
        let squareCard = null;
        let squareInitialising = false;
        let squareScriptPromise = null;

        document.addEventListener('click', (event) => {
            const googleLink = event.target.closest('[data-checkout-google-popup]');

            if (!googleLink) {
                return;
            }

            event.preventDefault();
            window.open(
                googleLink.href,
                'googleLogin',
                'width=520,height=680,menubar=no,toolbar=no,location=yes,status=no'
            );
        });

        function setCardButtonReady(ready) {
            const btn = document.getElementById('card-button');

            if (!btn) {
                return;
            }

            btn.disabled = !ready;
            btn.textContent = ready
                ? 'Pay £{{ number_format($this->getFinalPrice(), 2) }}'
                : 'Loading secure card form...';
        }

        function loadSquareScript() {
            if (window.Square?.payments) {
                return Promise.resolve(window.Square);
            }

            if (squareScriptPromise) {
                return squareScriptPromise;
            }

            squareScriptPromise = new Promise((resolve, reject) => {
                const existing = document.querySelector(`script[src="${squareSrc}"]`);

                if (existing) {
                    existing.addEventListener('load', () => resolve(window.Square), { once: true });
                    existing.addEventListener('error', () => reject(new Error('Square payment script failed to load.')), { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = squareSrc;
                script.async = true;
                script.onload = () => resolve(window.Square);
                script.onerror = () => reject(new Error('Square payment script failed to load.'));
                document.head.appendChild(script);
            });

            return squareScriptPromise;
        }

        function waitForSquare(timeoutMs = 15000) {
            const startedAt = Date.now();

            return new Promise((resolve, reject) => {
                const tick = () => {
                    if (window.Square?.payments) {
                        resolve(window.Square);
                        return;
                    }

                    if (Date.now() - startedAt > timeoutMs) {
                        reject(new Error('Square payment form did not load. Please refresh and try again.'));
                        return;
                    }

                    setTimeout(tick, 100);
                };

                tick();
            });
        }

        async function initCard() {
            const container = document.getElementById('card-container');

            if (!container || squareCard || squareInitialising) {
                return;
            }

            squareInitialising = true;
            setCardButtonReady(false);

            try {
                await loadSquareScript();
                const Square = await waitForSquare();
                const payments = Square.payments(appId, locationId);
                squareCard = await payments.card();
                await squareCard.attach('#card-container');
                bindPayButton();
                setCardButtonReady(true);
            } catch (err) {
                console.error('Square init failed:', err);
                container.innerHTML =
                    '<div class="alert alert-danger small">Could not initialise payment form: ' + err.message + ' <button type="button" class="btn btn-sm btn-link p-0" data-card-retry>Retry</button></div>';
                setCardButtonReady(false);
            } finally {
                squareInitialising = false;
            }
        }

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-card-retry]')) {
                return;
            }

            event.preventDefault();
            squareCard = null;
            squareScriptPromise = null;
            const container = document.getElementById('card-container');
            if (container) {
                container.innerHTML = '<div class="text-muted small py-3">Loading secure card form...</div>';
            }
            initCard();
        });

        function bindPayButton() {
            const btn = document.getElementById('card-button');
            btn.replaceWith(btn.cloneNode(true));
            const freshBtn = document.getElementById('card-button');

            freshBtn.addEventListener('click', async function() {
                if (!squareCard) return;
                freshBtn.disabled = true;
                freshBtn.textContent = 'Processing...';
                const errorsEl = document.getElementById('card-errors');
                errorsEl.style.display = 'none';

                try {
                    const result = await squareCard.tokenize();
                    if (result.status === 'OK') {
                        $wire.processPayment(result.token);
                    } else {
                        errorsEl.textContent = result.errors ? result.errors.map(e => e.message).join(', ') : 'Card validation failed.';
                        errorsEl.style.display = 'block';
                        freshBtn.disabled = false;
                        freshBtn.textContent = 'Try Again';
                    }
                } catch (err) {
                    errorsEl.textContent = 'Payment error. Please try again.';
                    errorsEl.style.display = 'block';
                    freshBtn.disabled = false;
                    freshBtn.textContent = 'Try Again';
                }
            });
        }

        // Re-bind the pay button after Livewire re-renders (e.g. after payment error)
        Livewire.hook('morph.updated', ({ el }) => {
            if (document.getElementById('card-button')) {
                if (squareCard) {
                    bindPayButton();
                    setCardButtonReady(true);
                } else {
                    initCard();
                }
            }
        });

        $wire.on('payment-complete', (event) => {
            window.location.href = event.url;
        });

        initCard();
    </script>
    @endscript
</div>
