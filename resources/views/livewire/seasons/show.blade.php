<div>
    @php
        $seasonCoverImageUrl = $season->cover_image_url;
    @endphp

    {{-- Season Hero --}}
    <section class="hero" style="padding: 3rem 0;">
        @if($seasonCoverImageUrl)
            <div class="hero-bg" style="background-image: url('{{ $seasonCoverImageUrl }}')"></div>
            <div class="hero-overlay"></div>
        @endif
        <div class="hero-content">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge-gold mb-2 d-inline-block">{{ $season->year }}</span>
                        <h1 class="h2 mb-2">{{ $season->name }}</h1>
                        @if($season->description)
                            <p class="lead small mb-0">{{ $season->description }}</p>
                        @endif
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        @auth
                            @if($hasSubscription)
                                <span class="badge-gold px-3 py-2">Season Pass Active</span>
                            @elseif($subscriptionAccessEnabled)
                                <div>
                                    <div class="text-white-50 small mb-1">Annual subscription</div>
                                    <div class="h4 text-white mb-2">&pound;{{ number_format($season->subscription_price, 2) }}</div>
                                    <a href="{{ route('checkout', ['type' => 'subscription', 'id' => $season->id]) }}" class="btn btn-secondary text-wrap text-start">
                                        Subscribe annually to access everything on the site
                                    </a>
                                </div>
                            @else
                                <div class="text-white-50 small">Season subscriptions are currently unavailable.</div>
                            @endif
                        @else
                            <a href="#publications" class="btn btn-outline-light">View Publications</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Publications Grid --}}
    <section class="section-sm" id="publications">
        <div class="container">
            @if($season->season_code === 'SS27')
                <div class="bg-light-gray p-4 p-md-5 mb-5">
                    <span class="badge-gold mb-3 d-inline-block">Spring/Summer 2027</span>
                    <h2 class="h4 font-serif mb-3">A Season Shaped by Contrast</h2>
                    <p class="text-muted mb-3">
                        Spring/Summer 2027 explores a season where decorative expression sits alongside quiet elegance. Across knitwear, the narrative moves between ornate romanticism and pared-back functionality, reflecting a broader shift in how contemporary fashion balances fantasy with everyday wearability.
                    </p>
                    <p class="text-muted mb-3">
                        Drawing from cultural, historical and natural references, the season unfolds through distinct yet complementary moods, each exploring a different facet of modern identity, place and craft.
                    </p>
                    <p class="text-muted mb-0">
                        Together, the reports form a seasonal perspective defined by nuance and atmosphere, where luxury is understood through feeling, material and the ease with which these worlds coexist.
                    </p>
                </div>
            @endif

            @if($publications->count())
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <h2 class="h4 font-serif mb-0">Publications</h2>
                </div>
                <div class="row g-4">
                    @foreach($publicationsForGrid as $publication)
                        @include('livewire.seasons.partials.publication-card', [
                            'cardColumnClass' => 'col-md-6 col-lg-4',
                        ])
                    @endforeach
                </div>

                {{-- Reused details modal: populated from the clicked publication card. --}}
                <div class="modal fade" id="publication-details-modal" tabindex="-1" aria-labelledby="publication-details-modal-label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title font-serif" id="publication-details-modal-label"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-md-5 d-none d-md-block">
                                        <img data-publication-modal-cover class="w-100 d-none" alt="" style="border: 1px solid rgba(56,56,56,0.06);">
                                        <div data-publication-modal-placeholder class="cover-placeholder d-none" style="height: 320px; font-size: 3rem;"></div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span data-publication-modal-season class="badge-gold d-inline-block"></span>
                                            <span data-publication-modal-pages class="badge-navy d-none"></span>
                                        </div>
                                        <h4 data-publication-modal-title class="font-serif mb-2"></h4>

                                        <div class="publication-includes-panel mb-3" data-publication-modal-format-panel>
                                            <div class="publication-includes-label">Purchase includes</div>
                                            <div class="publication-includes-options">
                                                <div class="publication-includes-option publication-includes-option--print" data-publication-modal-print-option>
                                                    <span class="publication-includes-icon" aria-hidden="true">P</span>
                                                    <span>Printed copy</span>
                                                </div>
                                                <div class="publication-includes-option">
                                                    <span class="publication-includes-icon" aria-hidden="true">D</span>
                                                    <span>Digital access</span>
                                                </div>
                                            </div>
                                            <div data-publication-modal-format-detail class="publication-includes-note"></div>
                                        </div>

                                        <p data-publication-modal-description class="text-muted"></p>

                                        <div data-publication-modal-price class="h4 font-serif text-gold mb-3 mt-2"></div>

                                        <div data-publication-modal-actions class="publication-modal-actions d-flex flex-wrap gap-2">
                                            <a data-publication-modal-view href="#" class="btn btn-primary d-none">View Flipbook</a>
                                            <a data-publication-modal-preview href="#" class="btn btn-outline-primary d-none">Preview</a>
                                            <a data-publication-modal-buy href="#" class="btn btn-primary d-none">Purchase</a>
                                            <a data-publication-modal-subscribe href="#" class="btn btn-outline-primary d-none">
                                                Subscribe to {{ $season->name }} {{ $season->year }}
                                            </a>
                                            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">No publications available in this season yet.</p>
                </div>
            @endif
        </div>
    </section>

    @push('scripts')
        <script>
            (() => {
                const modal = document.getElementById('publication-details-modal');

                if (!modal || modal.dataset.initialized === 'true') {
                    return;
                }

                modal.dataset.initialized = 'true';

                const setHidden = (element, hidden) => {
                    element.classList.toggle('d-none', hidden);
                };

                modal.addEventListener('show.bs.modal', (event) => {
                    const card = event.relatedTarget?.closest('.publication-card');

                    if (!card) {
                        return;
                    }

                    const data = card.dataset;
                    const cover = modal.querySelector('[data-publication-modal-cover]');
                    const placeholder = modal.querySelector('[data-publication-modal-placeholder]');
                    const description = modal.querySelector('[data-publication-modal-description]');
                    const price = modal.querySelector('[data-publication-modal-price]');
                    const viewLink = modal.querySelector('[data-publication-modal-view]');
                    const previewLink = modal.querySelector('[data-publication-modal-preview]');
                    const buyLink = modal.querySelector('[data-publication-modal-buy]');
                    const subscribeLink = modal.querySelector('[data-publication-modal-subscribe]');
                    const formatPanel = modal.querySelector('[data-publication-modal-format-panel]');
                    const printOption = modal.querySelector('[data-publication-modal-print-option]');
                    const formatDetail = modal.querySelector('[data-publication-modal-format-detail]');
                    const pages = modal.querySelector('[data-publication-modal-pages]');

                    modal.querySelector('#publication-details-modal-label').textContent = data.publicationTitle;
                    modal.querySelector('[data-publication-modal-title]').textContent = data.publicationTitle;
                    modal.querySelector('[data-publication-modal-season]').textContent = data.publicationSeason;
                    pages.textContent = data.publicationPageCount ? `${data.publicationPageCount} pages` : '';
                    setHidden(pages, !data.publicationPageCount);

                    const isDigitalOnly = data.publicationIsDigitalOnly === '1';
                    formatPanel.classList.toggle('publication-includes-panel--digital-only', isDigitalOnly);
                    printOption.classList.toggle('is-muted', isDigitalOnly);
                    formatDetail.textContent = data.publicationFormatDetail;

                    if (data.publicationCoverUrl) {
                        cover.src = data.publicationCoverUrl;
                        cover.alt = data.publicationTitle;
                        setHidden(cover, false);
                        setHidden(placeholder, true);
                    } else {
                        cover.removeAttribute('src');
                        cover.alt = '';
                        placeholder.textContent = data.publicationPlaceholder;
                        setHidden(cover, true);
                        setHidden(placeholder, false);
                    }

                    description.textContent = data.publicationDescription || '';
                    setHidden(description, !data.publicationDescription);

                    const canView = data.publicationCanView === '1';
                    const isAuthenticated = data.publicationIsAuthenticated === '1';
                    const hasSubscription = data.publicationHasSubscription === '1';
                    const canSubscribe = Boolean(data.publicationSubscribeUrl);

                    price.innerHTML = data.publicationPrice;
                    setHidden(price, canView);

                    viewLink.href = data.publicationViewUrl || '#';
                    previewLink.href = data.publicationPreviewUrl || '#';
                    buyLink.href = data.publicationBuyUrl || '#';
                    subscribeLink.href = data.publicationSubscribeUrl || '#';

                    setHidden(viewLink, !canView);
                    setHidden(previewLink, canView || !data.publicationPreviewUrl);
                    setHidden(buyLink, canView);
                    setHidden(subscribeLink, canView || !isAuthenticated || hasSubscription || !canSubscribe);
                });

                const requestedPublication = new URLSearchParams(window.location.search).get('publication');

                if (requestedPublication) {
                    const requestedCard = [...document.querySelectorAll('.publication-card')]
                        .find((card) => card.dataset.publicationSlug === requestedPublication);

                    const trigger = requestedCard?.querySelector('.publication-card-trigger');

                    if (trigger && window.bootstrap?.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modal).show(trigger);
                    }
                }
            })();
        </script>
    @endpush
</div>
