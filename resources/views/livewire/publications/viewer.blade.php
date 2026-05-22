<div>
    {{-- Viewer Navigation Bar --}}
    <div class="viewer-nav d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('seasons.show', $publication->season->slug) }}">
                &larr; {{ $publication->season->name }}
            </a>
            <span class="text-white-50">|</span>
            <span class="text-white small">{{ $publication->title }}</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if($previous)
                <a href="{{ route('publications.viewer', $previous->slug) }}">
                    &larr; Previous
                </a>
            @endif

            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em;">
                    All in Season
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

            @if($next)
                <a href="{{ route('publications.viewer', $next->slug) }}">
                    Next &rarr;
                </a>
            @endif
        </div>
    </div>

    {{-- Flipbook --}}
    @if($publication->pdf_file)
        <div class="flipbook-viewer">
            <iframe
                src="/flipbook-viewer/index.html?pdf={{ urlencode($publication->pdf_url) }}"
                style="width: 100%; height: calc(100vh - 60px); border: none;"
                allowfullscreen
            ></iframe>
        </div>
    @else
        <div class="flipbook-viewer d-flex align-items-center justify-content-center">
            <div class="text-center text-white">
                <h4>No PDF available</h4>
                <p class="text-white-50">This publication does not have a PDF uploaded yet.</p>
            </div>
        </div>
    @endif
</div>
