<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} — {{ config('app.name') }}</title>
    @include('partials.adobe-fonts')
    @stack('head')
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
        <div class="container">
            <x-brand-mark class="navbar-brand py-0" />
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('seasons.index') }}">Seasons</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">My Library</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('orders.index') }}">Orders</a>
                        </li>
                        @if(auth()->user()->is_admin)
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin</a>
                            </li>
                        @endif
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                {{ auth()->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                                <li><a class="dropdown-item" href="{{ route('account.settings') }}">Account Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Sign Out</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Sign In</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-primary btn-sm" href="{{ route('register') }}">Register</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    <footer class="bg-navy text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h6 class="text-white text-uppercase ls-wide small mb-3">Robert Todd Trends</h6>
                    <p class="text-white-50 small">Premium trend publications for the fashion and textile industry.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h6 class="text-white text-uppercase ls-wide small mb-3">Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('seasons.index') }}" class="text-white-50 text-decoration-none">Browse Seasons</a></li>
                        <li class="mb-2"><a href="{{ route('terms') }}" class="text-white-50 text-decoration-none">Terms & Conditions</a></li>
                        @auth
                            <li class="mb-2"><a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none">My Library</a></li>
                        @endauth
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white text-uppercase ls-wide small mb-3">Contact</h6>
                    <p class="text-white-50 small mb-0">Robert Todd Ltd</p>
                    <p class="text-white-50 small">trends@roberttodds.com</p>
                </div>
            </div>
            <hr class="border-white-10 my-4">
                    <p class="text-white-50 small mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>

    @include('partials.login-modal')

    @livewireScripts
    @stack('scripts')
</body>
</html>
