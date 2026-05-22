<x-layouts.app title="Home">
    {{-- Hero --}}
    <section class="home-hero">
        <div class="home-hero-bg" style="background-image: url('{{ asset('images/ss27-home-banner.jpg') }}')"></div>
        <div class="home-hero-shade"></div>
        <div class="container">
            <div class="home-hero-panel">
                <span class="badge-gold mb-3 d-inline-block">Spring Summer 2027</span>
                <h1 class="display-4 mb-3">Refined Knitwear Insight</h1>
                <div class="divider mb-4"></div>
                <p class="lead mb-3">
                    Spring/Summer 2027 trend reports from Robert Todd, knitwear specialists since 1888.
                </p>
                <p class="mb-4 text-muted">
                    Clear, expert views of the key shifts shaping seasonal direction, from trade-show intelligence and yarn innovation to design, retail and catwalk analysis.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('seasons.show', 'spring-summer-2027') }}" class="btn btn-primary">View Reports</a>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-outline-primary">Create Account</a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    {{-- Featured report --}}
    @php
        $ss27 = \App\Models\Season::where('slug', 'spring-summer-2027')->published()->first();
        $featuredPublication = $ss27?->publications()->published()->where('is_featured', true)->first();
        $reportCount = $ss27?->published_publications_count ?? 0;
        $minPages = $ss27 ? $ss27->publications()->published()->min('page_count') : null;
        $maxPages = $ss27 ? $ss27->publications()->published()->max('page_count') : null;
    @endphp
    @if($ss27 && $featuredPublication)
        <section class="section">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="section-heading">The Main SS27 Report</h2>
                    <div class="divider divider-center"></div>
                    <p class="section-subheading">Design Direction is the core seasonal report. Additional focused reports are available to support specific areas of research.</p>
                </div>

                <div class="home-featured-report">
                    <div class="row g-0 align-items-stretch">
                        <div class="col-lg-5">
                            <a href="{{ route('publications.preview', $featuredPublication->slug) }}" class="home-featured-report-cover">
                                <img src="{{ $featuredPublication->cover_image_url }}" alt="{{ $featuredPublication->title }}">
                                @if($featuredPublication->page_count)
                                    <span class="home-featured-report-pages">{{ $featuredPublication->page_count }} pages</span>
                                @endif
                            </a>
                        </div>
                        <div class="col-lg-7">
                            <div class="home-featured-report-copy">
                                <span class="badge-gold mb-3 d-inline-block">Main Report</span>
                                <h3 class="font-serif mb-3">{{ $featuredPublication->title }}</h3>
                                <p class="text-muted mb-4">
                                    This is the main Spring/Summer 2027 report to start with. Design Direction brings together the key seasonal shifts across knitwear, including shapes, styling, yarn specification, menswear and womenswear direction across distinct seasonal moods.
                                </p>
                                <p class="text-muted mb-4">
                                    The wider collection includes shorter focused reports covering catwalk, menswear, shop, yarn and trade-show intelligence. These can be viewed separately depending on the area of insight you need.
                                </p>
                                <div class="home-featured-stats mb-4">
                                    <div>
                                        <strong>Main</strong>
                                        <span>Design Direction</span>
                                    </div>
                                    @if($minPages && $maxPages)
                                        <div>
                                            <strong>{{ $featuredPublication->page_count }}</strong>
                                            <span>pages in main report</span>
                                        </div>
                                    @endif
                                    <div>
                                        <strong>{{ $reportCount - 1 }}</strong>
                                        <span>supporting reports</span>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-3">
                                    <a href="{{ route('publications.preview', $featuredPublication->slug) }}" class="btn btn-outline-primary">Preview Featured Report</a>
                                    <a href="{{ route('seasons.show', $ss27->slug) }}" class="btn btn-primary">View All Reports</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- How it works --}}
    <section class="section bg-light-gray">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-heading">How It Works</h2>
                <div class="divider divider-center"></div>
            </div>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="display-4 text-gold font-serif mb-3">01</div>
                    <h5>Preview Reports</h5>
                    <p class="text-muted small">Explore the latest Spring/Summer 2027 directions and preview each report before purchase.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="display-4 text-gold font-serif mb-3">02</div>
                    <h5>Purchase Securely</h5>
                    <p class="text-muted small">Buy individual reports online. Selected publications include a printed copy sent by UK delivery.</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="display-4 text-gold font-serif mb-3">03</div>
                    <h5>Read Online</h5>
                    <p class="text-muted small">Access every purchased report through the online viewer from any compatible device.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="section">
        <div class="container text-center">
            <h2 class="section-heading">Ready for the Season Ahead?</h2>
            <div class="divider divider-center"></div>
            <p class="section-subheading col-md-6 mx-auto">
                Discover the key directions set to define knitwear for Spring/Summer 2027.
            </p>
            @guest
                <a href="{{ route('register') }}" class="btn btn-primary">Create Your Account</a>
            @else
                <a href="{{ route('seasons.index') }}" class="btn btn-primary">Browse Seasons</a>
            @endguest
        </div>
    </section>
</x-layouts.app>
