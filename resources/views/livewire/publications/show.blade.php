<div>
    <section class="section-sm">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('seasons.index') }}">Seasons</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('seasons.show', $season->slug) }}">{{ $season->name }}</a></li>
                    <li class="breadcrumb-item active">{{ $publication->title }}</li>
                </ol>
            </nav>

            <div class="row g-5">
                <div class="col-md-5">
                    @if($publication->cover_image_url)
                        <img src="{{ $publication->cover_image_url }}" class="w-100" alt="{{ $publication->title }}" style="border: 1px solid rgba(56,56,56,0.06);">
                    @else
                        <div class="cover-placeholder" style="height: 400px; font-size: 4rem;">
                            {{ substr($publication->title, 0, 2) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-7">
                    <span class="badge-gold mb-2 d-inline-block">{{ $season->name }} {{ $season->year }}</span>
                    <h1 class="h2 font-serif mb-2">{{ $publication->title }}</h1>

                    <div class="mb-3">
                        @if($publication->is_digital_only)
                            <span class="badge-navy">Digital only</span>
                        @else
                            <span class="badge-gold">Print + digital</span>
                        @endif
                    </div>

                    @if($publication->description)
                        <p class="text-muted mb-4">{{ $publication->description }}</p>
                    @endif

                    @if($hasAccess)
                        <a href="{{ route('publications.viewer', $publication->slug) }}" class="btn btn-primary btn-lg">
                            View Flipbook
                        </a>
                    @else
                        <div class="mb-4">
                            <div class="h3 font-serif text-gold mb-3">&pound;{{ number_format($publication->price, 2) }}</div>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="{{ route('publications.preview', $publication->slug) }}" class="btn btn-outline-primary">
                                    Preview
                                </a>
                                <a href="{{ route('checkout', ['type' => 'publication', 'id' => $publication->id]) }}" class="btn btn-primary">
                                    Purchase
                                </a>
                                @if($subscriptionAccessEnabled && !auth()->user()->hasSubscription($season))
                                    <a href="{{ route('checkout', ['type' => 'subscription', 'id' => $season->id]) }}" class="btn btn-outline-primary text-wrap text-start">
                                        Subscribe annually to access everything on the site — &pound;{{ number_format($season->subscription_price, 2) }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
