<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">



    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Usando Vite -->
    @vite(['resources/js/app.js'])
</head>

<body>
    <div id="app">

        <header>

            @isset($navPartial)
                @include($navPartial)
            @else
                @auth
                    @php $u = auth()->user(); @endphp

                    @if ($u->hasRole('admin'))
                        @include('partials.nav.admin')
                    @elseif ($u->hasRole('operatore'))
                        @include('partials.nav.operator')
                    @elseif ($u->hasRole('cliente'))
                        @include('partials.nav.client')
                    @else
                        @include('partials.nav.guest')
                    @endif
                @else
                    @include('partials.nav.guest')
                @endauth
            @endisset


            {{-- PAGE HEADER (titolo, contatori, ecc.) opzionale --}}
            @auth
                @hasSection('page-header')
                    <div class="container mt-3">
                        @yield('page-header')
                    </div>
                @endif
            @endauth
        </header>

        <main>
            <div class="container">

                @auth
                    @include('partials.account-alerts')
                @endauth
                @include('partials.flash')
                @yield('content')
            </div>
        </main>

    </div>

    @stack('scripts')
</body>

</html>
