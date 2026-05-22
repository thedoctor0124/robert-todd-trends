@php
    $coverImageUrl = $publication->cover_image_url;
    $hasPurchased = $purchasedPublicationIds->contains($publication->id);
    $canView = $hasSubscription || $hasPurchased;
    $formatLabel = $publication->is_digital_only ? 'Digital only' : 'Print + digital';
    $cardColumnClass = $cardColumnClass ?? 'col-md-3 col-sm-6';
@endphp

<div class="{{ $cardColumnClass }}">
    <div
        class="card publication-card h-100{{ $publication->is_featured ? ' publication-card--featured' : '' }}"
        data-publication-title="{{ $publication->title }}"
        data-publication-slug="{{ $publication->slug }}"
        data-publication-description="{{ $publication->description }}"
        data-publication-cover-url="{{ $coverImageUrl }}"
        data-publication-placeholder="{{ substr($publication->title, 0, 2) }}"
        data-publication-season="{{ $season->name }} {{ $season->year }}"
        data-publication-page-count="{{ $publication->page_count }}"
        data-publication-format="{{ $formatLabel }}"
        data-publication-is-digital-only="{{ $publication->is_digital_only ? '1' : '0' }}"
        data-publication-format-detail="{{ $publication->is_digital_only ? 'Instant digital access' : 'Includes printed copy and digital access' }}"
        data-publication-format-class="{{ $publication->is_digital_only ? 'badge-navy' : 'badge-gold' }}"
        data-publication-price="&pound;{{ number_format($publication->price, 2) }}"
        data-publication-can-view="{{ $canView ? '1' : '0' }}"
        data-publication-is-authenticated="{{ auth()->check() ? '1' : '0' }}"
        data-publication-has-subscription="{{ $hasSubscription ? '1' : '0' }}"
        data-publication-view-url="{{ $canView ? route('publications.viewer', $publication->slug) : '' }}"
        data-publication-buy-url="{{ ! $canView ? route('checkout', ['type' => 'publication', 'id' => $publication->id]) : '' }}"
        data-publication-preview-url="{{ route('publications.preview', $publication->slug) }}"
        data-publication-subscribe-url="{{ $subscriptionAccessEnabled && auth()->check() && ! $hasSubscription ? route('checkout', ['type' => 'subscription', 'id' => $season->id]) : '' }}"
        data-publication-login-url="{{ route('login') }}"
    >
        <button type="button" class="publication-card-trigger" data-bs-toggle="modal" data-bs-target="#publication-details-modal" aria-label="View details for {{ $publication->title }}">
            @if($coverImageUrl)
                <div class="card-img-wrapper">
                    <img src="{{ $coverImageUrl }}" class="card-img-top" alt="{{ $publication->title }}">
                </div>
            @else
                <div class="cover-placeholder">
                    {{ substr($publication->title, 0, 2) }}
                </div>
            @endif
        </button>
        <div class="card-body d-flex flex-column">
            <button type="button" class="publication-card-trigger text-start p-0 bg-transparent border-0" data-bs-toggle="modal" data-bs-target="#publication-details-modal">
                <div class="publication-card-meta mb-3">
                    <span class="publication-card-format {{ $publication->is_digital_only ? 'publication-card-format--digital' : 'publication-card-format--print' }}">
                        {{ $formatLabel }}
                    </span>
                </div>
                <h6 class="card-title mb-2">{{ $publication->title }}</h6>
            </button>
            @if($publication->description)
                <p class="card-text small flex-grow-1">{{ Str::limit($publication->description, 80) }}</p>
            @endif
            <div class="publication-card-footer d-flex justify-content-between align-items-center mt-auto pt-3">
                @auth
                    @if($canView)
                        <a href="{{ route('publications.viewer', $publication->slug) }}" class="btn btn-sm btn-primary">View</a>
                        <span class="small text-muted">Access granted</span>
                    @else
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#publication-details-modal">Details</button>
                        <span class="price-tag">&pound;{{ number_format($publication->price, 2) }}</span>
                    @endif
                @else
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#publication-details-modal">Details</button>
                    <span class="price-tag">&pound;{{ number_format($publication->price, 2) }}</span>
                @endauth
            </div>
        </div>
    </div>
</div>
