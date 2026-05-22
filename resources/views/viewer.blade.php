<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $publication->title }} — {{ config('app.name') }}</title>
@include('partials.adobe-fonts')
@vite(['resources/scss/app.scss', 'resources/js/app.js'])
<style>
    :root { --viewer-toolbar-height: 52px; }
    body { margin: 0; padding: 0; overflow: hidden; background: rgb(56, 56, 56); }
    .viewer-frame,
    .flat-viewer { width: 100%; height: calc(100vh - var(--viewer-toolbar-height)); border: none; display: block; }
    .flat-viewer { overflow: auto; background: #2f2f2f; padding: 1.5rem 0; }
    .flat-viewer[hidden],
    .viewer-frame[hidden] { display: none; }
    .flat-page {
        display: block;
        width: min(1100px, calc(100vw - 2rem));
        margin: 0 auto 1.5rem;
        background: #fff;
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.35);
    }
    .flat-viewer-load-panel {
        max-width: 28rem;
        margin: 0 auto;
        padding: 2rem 1.5rem 2.5rem;
        text-align: center;
    }
    .flat-viewer-status {
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.9rem;
        margin-bottom: 0;
    }
    .flat-viewer-progress {
        max-width: 100%;
        margin: 1rem auto 0;
        height: 8px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 4px;
        overflow: hidden;
    }
    .flat-viewer-progress[hidden] {
        display: none !important;
    }
    .flat-viewer-progress-fill {
        display: block;
        height: 100%;
        min-height: 8px;
        width: 0%;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 0 12px rgba(255, 255, 255, 0.35);
        transition: width 0.2s ease-out;
    }
    .flat-viewer-progress-fill.is-indeterminate {
        width: 42% !important;
        animation: flat-progress-scan 1.05s ease-in-out infinite alternate;
    }
    @keyframes flat-progress-scan {
        0% { transform: translateX(-30%); opacity: 0.75; }
        100% { transform: translateX(220%); opacity: 1; }
    }
    .viewer-mode-toggle .btn {
        font-size: 0.7rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .flat-preview-banner {
        max-width: 1100px;
        margin: 0 auto 1rem;
        padding: 0.85rem 1rem;
        color: #fff;
        background: rgba(201, 169, 110, 0.9);
        font-size: 0.82rem;
        letter-spacing: 0.06em;
        text-align: center;
        text-transform: uppercase;
    }
    .flat-preview-banner a {
        color: #fff;
        font-weight: 700;
    }
    .flat-preview-cta {
        max-width: 1100px;
        margin: 0 auto 2rem;
        padding: 0 1rem;
    }
    .flat-preview-cta-inner {
        padding: 2rem;
        color: #fff;
        background: rgb(56, 56, 56);
        border: 1px solid rgba(255,255,255,0.18);
        text-align: center;
    }
    .flat-preview-cta h3 {
        margin-bottom: 0.5rem;
        color: #fff;
        font-size: 1.2rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }
    .flat-preview-cta p {
        color: rgba(255,255,255,0.72);
    }
    .viewer-toolbar {
        height: var(--viewer-toolbar-height);
        background-color: rgb(56, 56, 56);
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        z-index: 3000;
        position: relative;
    }
    .viewer-toolbar-main,
    .viewer-toolbar-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }
    .viewer-toolbar-link,
    .viewer-toolbar-title {
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-family: inherit;
        white-space: nowrap;
    }
    .viewer-toolbar-title {
        color: #fff;
        text-transform: none;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .viewer-toolbar-separator {
        color: rgba(255,255,255,0.3);
    }
    .viewer-season-short {
        display: none;
    }
    .viewer-toolbar .dropdown-menu {
        z-index: 3100;
    }
    @media (max-width: 767.98px) {
        :root { --viewer-toolbar-height: 104px; }
        .viewer-toolbar {
            align-items: stretch;
            flex-direction: column;
            justify-content: center;
            padding: 0.45rem 0.5rem;
            gap: 0.45rem;
        }
        .viewer-toolbar-main,
        .viewer-toolbar-actions {
            width: 100%;
            gap: 0.45rem;
        }
        .viewer-toolbar-main {
            justify-content: flex-start;
        }
        .viewer-toolbar-actions {
            justify-content: flex-start;
            overflow: visible;
        }
        .viewer-toolbar-link,
        .viewer-toolbar-title {
            font-size: 0.72rem;
            letter-spacing: 0.06em;
        }
        .viewer-toolbar-title {
            flex: 1 1 auto;
        }
        .viewer-season-full,
        .viewer-toolbar-separator,
        .viewer-prev-next {
            display: none;
        }
        .viewer-season-short {
            display: inline;
        }
        .viewer-mode-toggle .btn,
        .viewer-toolbar-actions .btn {
            font-size: 0.66rem !important;
            letter-spacing: 0.06em !important;
            padding: 0.5rem 0.65rem;
            white-space: nowrap;
        }
        .viewer-toolbar-actions .dropdown {
            flex: 0 0 auto;
        }
        .viewer-toolbar .dropdown-menu {
            max-width: calc(100vw - 1rem);
            white-space: normal;
        }
    }
</style>
</head>
<body class="viewer-page">
@php
    $defaultViewerMode = $publication->default_viewer_mode === 'flat' ? 'flat' : 'flipbook';
    $isPreview = $isPreview ?? false;
    $previewPageLimit = (int) ($previewPageLimit ?? 0);
    $previewHasAccess = auth()->check() && auth()->user()->hasAccessToPublication($publication);
@endphp

{{-- Navigation Bar --}}
<div class="viewer-toolbar">
    <div class="viewer-toolbar-main">
        <a href="{{ route('seasons.show', $publication->season->slug) }}" class="viewer-toolbar-link">
            &larr; <span class="viewer-season-full">{{ $publication->season->name }}</span><span class="viewer-season-short">Season</span>
        </a>
        <span class="viewer-toolbar-separator">|</span>
        <span class="viewer-toolbar-title">{{ $publication->title }}</span>
        @if($isPreview)
            <span class="badge-gold">Preview</span>
        @endif
    </div>
    <div class="viewer-toolbar-actions">
        @if($isPreview)
            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#preview-purchase-modal">
                Purchase Options
            </button>
        @endif
        @if($pdfUrl)
            <div class="btn-group btn-group-sm viewer-mode-toggle" role="group" aria-label="Viewer mode">
                <button type="button" class="btn {{ $defaultViewerMode === 'flipbook' ? 'btn-light active' : 'btn-outline-light' }}" data-viewer-mode="flipbook" aria-pressed="{{ $defaultViewerMode === 'flipbook' ? 'true' : 'false' }}">
                    Flipbook
                </button>
                <button type="button" class="btn {{ $defaultViewerMode === 'flat' ? 'btn-light active' : 'btn-outline-light' }}" data-viewer-mode="flat" aria-pressed="{{ $defaultViewerMode === 'flat' ? 'true' : 'false' }}">
                    Flat PDF
                </button>
            </div>
        @endif

        @if($previous)
            <a href="{{ route('publications.viewer', $previous->slug) }}" class="viewer-toolbar-link viewer-prev-next">
                &larr; Previous
            </a>
        @endif

        @unless($isPreview)
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em;">
                    Seasons Publications
                </button>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                    @foreach($seasonPublications as $pub)
                        <li>
                            <a class="dropdown-item small {{ $pub->id === $publication->id ? 'active' : '' }}"
                               href="{{ route('publications.viewer', $pub->slug) }}">
                                {{ $pub->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endunless

        @if($next)
            <a href="{{ route('publications.viewer', $next->slug) }}" class="viewer-toolbar-link viewer-prev-next">
                Next &rarr;
            </a>
        @endif
    </div>
</div>

{{-- Flipbook - served from static Heyzine viewer with PDF path as query param --}}
@if($pdfUrl)
    <iframe
        id="flipbook-viewer"
        class="viewer-frame"
        data-src="/flipbook-viewer/index.html?pdf={{ urlencode($pdfUrl) }}&v=page-count-fix"
        @if($defaultViewerMode === 'flipbook') src="/flipbook-viewer/index.html?pdf={{ urlencode($pdfUrl) }}&v=page-count-fix" @else hidden @endif
        allowfullscreen
    ></iframe>
    <div id="flat-viewer" class="flat-viewer" @if($defaultViewerMode === 'flipbook') hidden @endif data-pdf-url="{{ $pdfUrl }}" data-default-mode="{{ $defaultViewerMode }}">
        @if($isPreview)
            <div class="flat-preview-banner">
                Preview: first {{ $previewPageLimit }} pages only.
                Return to purchase options to unlock full access.
            </div>
        @endif
        <div class="flat-viewer-load-panel" data-flat-load-panel>
            <p class="flat-viewer-status mb-0" data-flat-status>@if($defaultViewerMode === 'flat')Preparing PDF…@else Click &ldquo;Flat PDF&rdquo; to load a simple page-by-page view.@endif</p>
            <div class="flat-viewer-progress" data-flat-progress-wrap hidden role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Download progress">
                <span class="flat-viewer-progress-fill" data-flat-progress-bar style="width: 0%;"></span>
            </div>
        </div>
    </div>
@else
    <div style="display: flex; align-items: center; justify-content: center; height: calc(100vh - 52px); color: white; font-family: inherit;">
        <div style="text-align: center;">
            <h4>No PDF available</h4>
            <p style="color: rgba(255,255,255,0.5);">This publication does not have a PDF uploaded yet.</p>
        </div>
    </div>
@endif

@if($pdfUrl)
    <script src="/flipbook-viewer/flipbook/js/site/pdf.min.js"></script>
    <script>
        (() => {
            const flipbookViewer = document.getElementById('flipbook-viewer');
            const flatViewer = document.getElementById('flat-viewer');
            const modeButtons = document.querySelectorAll('[data-viewer-mode]');

            if (!flipbookViewer || !flatViewer) {
                return;
            }

            const previewPageLimit = {{ $isPreview ? $previewPageLimit : 0 }};
            const configuredDefaultMode = flatViewer.dataset.defaultMode === 'flat' ? 'flat' : 'flipbook';
            const defaultMode = previewPageLimit > 0 || window.matchMedia('(max-width: 767.98px)').matches ? 'flat' : configuredDefaultMode;
            let pdfDocument = null;
            let flatViewLoaded = false;
            let pdfJs = null;

            const getPdfJs = async () => {
                if (pdfJs) {
                    return pdfJs;
                }

                if (window.pdfjsLib) {
                    pdfJs = window.pdfjsLib;
                } else {
                    const module = await import('/flipbook-viewer/flipbook/js/site/pdf.min.js');
                    pdfJs = module.getDocument ? module : window.pdfjsLib;
                }

                if (!pdfJs) {
                    throw new Error('PDF.js did not load.');
                }

                pdfJs.GlobalWorkerOptions.workerSrc = '/flipbook-viewer/flipbook/js/site/pdf.worker.min.js';

                return pdfJs;
            };

            const setActiveMode = (mode) => {
                const showFlat = mode === 'flat';

                if (!showFlat && !flipbookViewer.getAttribute('src')) {
                    flipbookViewer.setAttribute('src', flipbookViewer.dataset.src);
                }

                flipbookViewer.hidden = showFlat;
                flatViewer.hidden = !showFlat;

                modeButtons.forEach((button) => {
                    const isActive = button.dataset.viewerMode === mode;
                    button.classList.toggle('active', isActive);
                    button.classList.toggle('btn-light', isActive);
                    button.classList.toggle('btn-outline-light', !isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                if (showFlat && !flatViewLoaded) {
                    flatViewLoaded = true;
                    loadFlatView();
                }
            };

            const renderPage = async (pageNumber) => {
                const page = await pdfDocument.getPage(pageNumber);
                const unscaledViewport = page.getViewport({ scale: 1 });
                const maxWidth = Math.max(280, Math.min(1100, window.innerWidth - 32));
                const cssScale = Math.max(0.1, maxWidth / unscaledViewport.width);
                const pixelRatio = Math.min(window.devicePixelRatio || 1, 3);
                const viewport = page.getViewport({ scale: cssScale });
                const renderViewport = page.getViewport({ scale: cssScale * pixelRatio });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');

                canvas.className = 'flat-page';
                canvas.width = Math.floor(renderViewport.width);
                canvas.height = Math.floor(renderViewport.height);
                canvas.style.width = `${Math.floor(viewport.width)}px`;
                canvas.style.height = `${Math.floor(viewport.height)}px`;
                canvas.setAttribute('aria-label', `Page ${pageNumber}`);
                flatViewer.appendChild(canvas);

                await page.render({ canvasContext: context, viewport: renderViewport }).promise;
            };

            const loadFlatView = async () => {
                const loadPanel = flatViewer.querySelector('[data-flat-load-panel]');
                const status = flatViewer.querySelector('[data-flat-status]');
                const progressWrap = flatViewer.querySelector('[data-flat-progress-wrap]');
                const progressBar = flatViewer.querySelector('[data-flat-progress-bar]');

                const setBarPct = (pct) => {
                    const clamped = Math.min(100, Math.max(0, pct));
                    progressBar.classList.remove('is-indeterminate');
                    progressBar.style.transform = '';
                    progressBar.style.width = `${clamped}%`;
                    progressWrap.setAttribute('aria-valuenow', String(Math.round(clamped)));
                };

                const setIndeterminate = (on) => {
                    progressBar.classList.toggle('is-indeterminate', on);
                    if (on) {
                        progressBar.style.width = '42%';
                    }
                };

                progressWrap.hidden = false;
                setBarPct(0);
                setIndeterminate(true);
                status.textContent = 'Loading PDF…';

                try {
                    const pdfApi = await getPdfJs();

                    const loadingTask = pdfApi.getDocument({
                        url: flatViewer.dataset.pdfUrl,
                        cMapUrl: '/flipbook-viewer/flipbook/js/site/bcmaps/',
                        cMapPacked: true,
                    });

                    loadingTask.onProgress = ({ loaded, total }) => {
                        if (total > 0) {
                            setIndeterminate(false);
                            const pct = Math.min(100, (loaded / total) * 100);
                            setBarPct(pct);
                            status.textContent = `Downloading PDF… ${Math.round(pct)}%`;
                        } else {
                            status.textContent = 'Loading PDF…';
                        }
                    };

                    pdfDocument = await loadingTask.promise;

                    setIndeterminate(false);
                    setBarPct(100);
                    progressWrap.hidden = true;

                    const n = pdfDocument.numPages;

                    const pagesToRender = previewPageLimit > 0 ? Math.min(previewPageLimit, n) : n;

                    for (let pageNumber = 1; pageNumber <= pagesToRender; pageNumber += 1) {
                        status.textContent = `Drawing pages… ${pageNumber} of ${pagesToRender}`;
                        await renderPage(pageNumber);
                    }

                    loadPanel.remove();

                    if (previewPageLimit > 0) {
                        const cta = document.createElement('div');
                        cta.className = 'flat-preview-cta';
                        cta.innerHTML = `
                            <div class="flat-preview-cta-inner">
                                <h3>Continue Reading</h3>
                                <p>This preview shows the first ${pagesToRender} pages. Purchase the publication to unlock the full report.</p>
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#preview-purchase-modal">View Purchase Options</button>
                            </div>
                        `;
                        flatViewer.appendChild(cta);
                    }
                } catch (error) {
                    console.error(error);
                    setIndeterminate(false);
                    progressBar.style.transform = '';
                    setBarPct(0);
                    status.textContent = 'Sorry, this PDF could not be loaded in flat view. Please use the flipbook view for now.';
                }
            };

            modeButtons.forEach((button) => {
                button.addEventListener('click', () => setActiveMode(button.dataset.viewerMode));
            });

            setActiveMode(defaultMode);

            document.addEventListener('contextmenu', (event) => {
                if (!flatViewer.hidden && event.target.closest('#flat-viewer')) {
                    event.preventDefault();
                }
            });
        })();
    </script>
@endif

@if($isPreview)
    <div class="modal fade" id="preview-purchase-modal" tabindex="-1" aria-labelledby="preview-purchase-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title font-serif" id="preview-purchase-modal-label">{{ $publication->title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <span class="badge-gold mb-3 d-inline-block">Preview</span>
                    <h4 class="font-serif mb-3">Unlock the full publication</h4>
                    <p class="text-muted">
                        This preview contains the first {{ $previewPageLimit }} pages only. Purchase the publication to access the full report online.
                    </p>
                    <div class="h4 font-serif text-gold mb-4">&pound;{{ number_format($publication->price, 2) }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        @if($previewHasAccess)
                            <a href="{{ route('publications.viewer', $publication->slug) }}" class="btn btn-primary">View Full Publication</a>
                        @elseif(auth()->check())
                            <a href="{{ route('checkout', ['type' => 'publication', 'id' => $publication->id]) }}" class="btn btn-primary">Buy This Publication</a>
                        @else
                            <a href="{{ route('checkout', ['type' => 'publication', 'id' => $publication->id]) }}" class="btn btn-primary">Checkout</a>
                        @endif
                        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Continue Preview</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @guest
        <div class="modal fade" id="preview-login-modal" tabindex="-1" aria-labelledby="preview-login-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header border-0">
                        <h5 class="modal-title font-serif" id="preview-login-modal-label">Sign In</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-4">Sign in to purchase and unlock the full publication.</p>
                        <div class="alert alert-danger small py-2 d-none" data-preview-login-error></div>

                        <a href="{{ route('auth.google', ['popup' => 1]) }}" class="btn google-btn w-100 mb-4" data-preview-google-popup>
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

                        <form data-preview-login-form>
                            <div class="mb-3">
                                <label for="preview-login-email" class="form-label small text-uppercase ls-wide">Email</label>
                                <input type="email" class="form-control" id="preview-login-email" name="email" required autocomplete="email">
                            </div>
                            <div class="mb-3">
                                <label for="preview-login-password" class="form-label small text-uppercase ls-wide">Password</label>
                                <input type="password" class="form-control" id="preview-login-password" name="password" required autocomplete="current-password">
                            </div>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="preview-login-remember" name="remember" value="1">
                                <label class="form-check-label small" for="preview-login-remember">Remember me</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" data-preview-login-submit>Sign In</button>
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
                const form = document.querySelector('[data-preview-login-form]');

                if (!form) {
                    return;
                }

                const error = document.querySelector('[data-preview-login-error]');
                const submit = document.querySelector('[data-preview-login-submit]');

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

                document.addEventListener('click', (event) => {
                    const googleLink = event.target.closest('[data-preview-google-popup]');

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
            })();
        </script>
    @endguest
@endif

</body>
</html>
