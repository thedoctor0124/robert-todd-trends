<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign In' }} — {{ config('app.name') }}</title>
    @include('partials.adobe-fonts')
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-light bg-white py-3">
        <div class="container">
            <x-brand-mark class="navbar-brand py-0" />
        </div>
    </nav>

    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            {{ $slot }}
        </div>
    </main>

    @include('partials.login-modal')

    @livewireScripts
</body>
</html>
