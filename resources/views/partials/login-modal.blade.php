@guest
    <div class="modal fade" id="site-login-modal" tabindex="-1" aria-labelledby="site-login-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title font-serif" id="site-login-modal-label">Sign In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-4">Sign in to purchase and access your trend publications.</p>
                    <div class="alert alert-danger small py-2 d-none" data-site-login-error></div>

                    <a href="{{ route('auth.google', ['popup' => 1]) }}" class="btn google-btn w-100 mb-4" data-google-popup>
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
                        <span class="px-3 text-muted small">or sign in with email</span>
                        <hr class="flex-grow-1">
                    </div>

                    <form data-site-login-form>
                        <div class="mb-3">
                            <label for="site-login-email" class="form-label small text-uppercase ls-wide">Email</label>
                            <input type="email" class="form-control" id="site-login-email" name="email" required autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <label for="site-login-password" class="form-label small text-uppercase ls-wide">Password</label>
                            <input type="password" class="form-control" id="site-login-password" name="password" required autocomplete="current-password">
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="site-login-remember" name="remember" value="1">
                            <label class="form-check-label small" for="site-login-remember">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" data-site-login-submit>Sign In</button>
                    </form>

                    <p class="text-center mt-4 mb-0 small">
                        Need an account? <a href="{{ route('register') }}">Register</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('site-login-modal');
            const form = document.querySelector('[data-site-login-form]');

            if (!modal || !form) {
                return;
            }

            const loginUrl = @json(route('login'));
            const error = document.querySelector('[data-site-login-error]');
            const submit = document.querySelector('[data-site-login-submit]');

            document.addEventListener('click', (event) => {
                const googleLink = event.target.closest('[data-google-popup]');

                if (googleLink) {
                    event.preventDefault();
                    window.open(
                        googleLink.href,
                        'googleLogin',
                        'width=520,height=680,menubar=no,toolbar=no,location=yes,status=no'
                    );

                    return;
                }

                const link = event.target.closest(`a[href="${loginUrl}"]`);

                if (!link || link.target === '_blank' || link.dataset.noLoginModal === 'true') {
                    return;
                }

                event.preventDefault();
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                error.classList.add('d-none');
                error.textContent = '';
                submit.disabled = true;
                submit.textContent = 'Signing in...';

                try {
                    const response = await fetch(@json(route('preview.login')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: new FormData(form),
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || 'Sign in failed. Please try again.');
                    }

                    window.location.reload();
                } catch (err) {
                    error.textContent = err.message;
                    error.classList.remove('d-none');
                    submit.disabled = false;
                    submit.textContent = 'Sign In';
                }
            });
        })();
    </script>
@endguest
