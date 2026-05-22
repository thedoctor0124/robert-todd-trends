<div>
    <section class="hero" style="padding: 3rem 0;">
        <div class="hero-content">
            <div class="container">
                <h1 class="h3 mb-1">My Library</h1>
                <p class="lead small mb-0">Your purchased publications and subscriptions.</p>
            </div>
        </div>
    </section>

    <section class="section-sm">
        <div class="container">
            {{-- Active Subscriptions --}}
            <div class="d-flex justify-content-end mb-4">
                <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">View Orders & Invoices</a>
            </div>

            {{-- Active Subscriptions --}}
            @if($subscriptions->count())
                <h4 class="font-serif mb-3">Season Subscriptions</h4>
                <div class="row g-3 mb-5">
                    @foreach($subscriptions as $subscription)
                        <div class="col-md-4">
                            <div class="card publication-card h-100">
                                <div class="card-body d-flex flex-column">
                                    <span class="badge-gold mb-2 d-inline-block" style="width: fit-content;">Season Pass</span>
                                    <h5 class="card-title">{{ $subscription->season->name }}</h5>
                                    <p class="card-text flex-grow-1">{{ $subscription->season->year }}</p>
                                    <a href="{{ route('seasons.show', $subscription->season->slug) }}" class="btn btn-sm btn-outline-primary mt-2">
                                        View Publications
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Individual Purchases --}}
            @if($purchases->count())
                <h4 class="font-serif mb-3">Purchased Publications</h4>
                <div class="row g-3 mb-5">
                    @foreach($purchases as $purchase)
                        <div class="col-md-3">
                            <div class="card publication-card h-100">
                                @if($purchase->publication->cover_image_url)
                                    <div class="card-img-wrapper">
                                        <img src="{{ $purchase->publication->cover_image_url }}" class="card-img-top" alt="{{ $purchase->publication->title }}">
                                    </div>
                                @else
                                    <div class="cover-placeholder">
                                        {{ substr($purchase->publication->title, 0, 2) }}
                                    </div>
                                @endif
                                <div class="card-body d-flex flex-column">
                                    <small class="text-muted">{{ $purchase->publication->season->name }}</small>
                                    <h6 class="card-title mt-1">{{ $purchase->publication->title }}</h6>
                                    <a href="{{ route('publications.viewer', $purchase->publication->slug) }}" class="btn btn-sm btn-primary mt-auto">
                                        View Flipbook
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!$subscriptions->count() && !$purchases->count())
                <div class="text-center py-5">
                    <h4 class="font-serif mb-2">Your library is empty</h4>
                    <p class="text-muted mb-4">Browse our seasons to find trend publications.</p>
                    <a href="{{ route('seasons.index') }}" class="btn btn-primary">Browse Seasons</a>
                </div>
            @endif
        </div>
    </section>
</div>
