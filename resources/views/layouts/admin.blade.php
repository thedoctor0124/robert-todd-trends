<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin — {{ $title ?? '' }} — {{ config('app.name') }}</title>
    @include('partials.adobe-fonts')
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <aside class="admin-sidebar d-flex flex-column">
        <div class="admin-header">
            <x-brand-mark variant="dark" />
            <div class="text-white-50 small mt-1">Administration</div>
        </div>
        <nav class="flex-grow-1 py-3">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.seasons.index') }}" class="nav-link {{ request()->routeIs('admin.seasons.*') ? 'active' : '' }}">
                Seasons
            </a>
            <a href="{{ route('admin.publications.index') }}" class="nav-link {{ request()->routeIs('admin.publications.*') ? 'active' : '' }}">
                Publications
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                Users
            </a>
            <a href="{{ route('admin.discount-codes.index') }}" class="nav-link {{ request()->routeIs('admin.discount-codes.*') ? 'active' : '' }}">
                Discount Codes
            </a>
            <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                Orders
            </a>
        </nav>
        <div class="p-3 border-top border-white-10">
            <a href="{{ route('dashboard') }}" class="nav-link small">
                &larr; Back to Site
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="nav-link small border-0 bg-transparent p-0 text-start w-100" style="color: rgba(255,255,255,0.7);">
                    Sign Out
                </button>
            </form>
        </div>
    </aside>

    <div class="admin-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
