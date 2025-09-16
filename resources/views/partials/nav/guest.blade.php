<nav class="navbar navbar-expand-md navbar-dark my-bg-brand-700 shadow-sm navbar-fixed-height navbar-layer"
    aria-label="Main">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ Vite::asset('resources/img/logo-no-space.png') }}" alt="Logo" class="img-fluid">
        </a>
    </div>

    {{-- Onda attaccata al fondo della NAV --}}
    <div class="navbar-wave" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" preserveAspectRatio="none"
            style="transform: scaleX(-1);">
            <path d="M0,0 C240,90 480,-10 720,30 C960,70 1200,30 1440,60 L1440,0 L0,0 Z" />
        </svg>
    </div>
</nav>
