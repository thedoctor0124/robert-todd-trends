<div>
    <section class="hero" style="padding: 3rem 0;">
        <div class="hero-content">
            <div class="container">
                <h1 class="h3 mb-1">Spring/Summer 2027 Reports</h1>
                <p class="lead small mb-0">
                    A refined perspective on knitwear, shaped by contrast, clarity and directional seasonal insight.
                </p>
            </div>
        </div>
    </section>

    <section class="section-sm">
        <div class="container">
            @if($seasons->count())
                @php $grouped = $seasons->groupBy('year'); @endphp
                @foreach($grouped as $year => $yearSeasons)
                    <h4 class="font-serif mb-3 mt-4">{{ $year }}</h4>
                    <div class="row g-4 mb-4">
                        @foreach($yearSeasons as $season)
                            <div class="col-md-4">
                                <a href="{{ route('seasons.show', $season->slug) }}" class="text-decoration-none">
                                    <div class="card season-card">
                                        @if($season->cover_image_url)
                                            <img src="{{ $season->cover_image_url }}" class="season-card-img w-100" alt="{{ $season->name }}">
                                        @else
                                            <div class="season-cover-placeholder">
                                                {{ $season->season_code }}
                                            </div>
                                        @endif
                                        <div class="season-card-overlay">
                                            <h5 class="mb-1 text-white">{{ $season->name }}</h5>
                                            <span class="small text-white-50">{{ $season->published_publications_count }} publications</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @else
                <div class="text-center py-5">
                    <h4 class="font-serif mb-2">No seasons available yet</h4>
                    <p class="text-muted">Check back soon for new publications.</p>
                </div>
            @endif
        </div>
    </section>
</div>
