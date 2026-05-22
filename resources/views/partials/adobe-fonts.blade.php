{{-- Adobe Fonts (Typekit): add Adobe Garamond + Gill Sans to a web project at https://fonts.adobe.com then set ADOBE_FONTS_KIT_ID in .env --}}
@if($kit = config('services.adobe_fonts.kit_id'))
    <link rel="stylesheet" href="https://use.typekit.net/{{ $kit }}.css">
@endif
