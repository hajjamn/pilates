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

                {{-- Reminder: verifica email --}}
                @auth
                    @if (!auth()->user()->hasVerifiedEmail())
                        <div class="alert alert-warning d-flex justify-content-between align-items-center my-3"
                            role="alert">
                            <div>
                                <strong>Verifica l’email:</strong> per usare tutte le funzionalità devi confermare il tuo
                                indirizzo.
                                Controlla la posta oppure richiedi un nuovo link.
                            </div>
                            <form class="ms-3" method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                    Invia di nuovo
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth

                {{-- Reminder: aggiungi telefono --}}
                @auth
                    @php $u = auth()->user(); @endphp
                    @if (blank($u->phone))
                        <div class="alert alert-info d-flex justify-content-between align-items-center my-3" role="alert">
                            <div>
                                <strong>Completa il profilo:</strong> aggiungi il tuo numero di telefono per essere
                                ricontattato rapidamente.
                            </div>
                            <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-primary">
                                Aggiungi telefono
                            </a>
                        </div>
                    @endif
                @endauth


                @include('partials.flash')
                @yield('content')
            </div>
        </main>

    </div>

    @stack('scripts')
</body>

</html>
