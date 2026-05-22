@props([
    'variant' => 'light',
])

@php
    $isCompact = $variant === 'dark' || $variant === 'footer';
@endphp

<a {{ $attributes->merge([
    'class' => 'brand-mark d-inline-flex flex-column align-items-center text-decoration-none',
    'href' => url('/'),
]) }}>
    <img
        src="{{ asset('images/logo.png') }}"
        alt="{{ config('app.name') }}"
        class="brand-mark__logo {{ $isCompact ? 'brand-mark__logo--sm' : 'brand-mark__logo--header' }}"
        @unless($isCompact)
            height="100"
            width="auto"
            style="height: 100px; width: auto; object-fit: contain; max-width: min(100%, calc(100vw - 2rem));"
        @endunless
        loading="lazy"
        decoding="async"
    >
</a>
