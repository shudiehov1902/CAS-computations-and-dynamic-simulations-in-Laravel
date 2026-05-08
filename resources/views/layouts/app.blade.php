<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? __('messages.app_name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header class="site-header">
            <nav class="nav-shell" aria-label="{{ __('messages.main_navigation') }}">
                <a class="brand" href="{{ route('home') }}">{{ __('messages.app_name') }}</a>

                <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
                    <span class="sr-only">{{ __('messages.toggle_navigation') }}</span>
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="nav-links" id="primary-navigation">
                    <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>{{ __('messages.nav_home') }}</a>
                    <a href="{{ route('cas-console') }}" @class(['active' => request()->routeIs('cas-console')])>{{ __('messages.nav_cas') }}</a>
                    <a href="{{ route('pendulum') }}" @class(['active' => request()->routeIs('pendulum')])>{{ __('messages.nav_pendulum') }}</a>
                    <a href="{{ route('ball-beam') }}" @class(['active' => request()->routeIs('ball-beam')])>{{ __('messages.nav_ball_beam') }}</a>
                    <a href="{{ route('logs') }}" @class(['active' => request()->routeIs('logs')])>{{ __('messages.nav_logs') }}</a>
                    <a href="{{ route('statistics') }}" @class(['active' => request()->routeIs('statistics')])>{{ __('messages.nav_statistics') }}</a>
                    <a href="{{ route('api-docs') }}" @class(['active' => request()->routeIs('api-docs')])>{{ __('messages.nav_api_docs') }}</a>
                </div>

                <div class="language-switcher" aria-label="{{ __('messages.language_switcher') }}">
                    <a href="{{ route('language.switch', 'sk') }}" @class(['active' => app()->getLocale() === 'sk'])>SK</a>
                    <a href="{{ route('language.switch', 'en') }}" @class(['active' => app()->getLocale() === 'en'])>EN</a>
                </div>
            </nav>
        </header>

        <main class="page-shell">
            @yield('content')
        </main>
    </body>
</html>
