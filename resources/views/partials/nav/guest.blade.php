<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ Vite::asset('resources/img/nav-logo.png') }}" alt="Logo" style="height: 40px;">
        </a>
        <div class="d-flex ms-auto">
            <a class="btn btn-outline-primary me-2" href="{{ route('login') }}">Login</a>
            @if (Route::has('register'))
                <a class="btn btn-primary" href="{{ route('register') }}">Registrati</a>
            @endif
        </div>
    </div>
</nav>
