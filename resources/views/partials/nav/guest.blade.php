<nav class="navbar navbar-expand-md navbar-dark my-bg-brand-700 shadow-sm navbar-fixed-height navbar-layer"
    aria-label="Main">
    <div class="container d-flex align-items-center">
        <!-- RENDI L'ANCHOR FULL-WIDTH E SENZA FLEX UTILS -->
        <a class="navbar-brand brand-with-title" href="{{ url('/') }}">
            <span class="brand-logo-circle">
                <img src="{{ Vite::asset('resources/img/logo-no-space.png') }}" alt="Logo">
            </span>

            <!-- Titolo centrato -->
            <h2 class="brand-title my-header-title">Centro Pilates - Ada Turco</h2>
        </a>
    </div>

    <div class="navbar-wave" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" preserveAspectRatio="none"
            style="transform: scaleX(-1);">
            <path d="M0,0 C240,90 480,-10 720,30 C960,70 1200,30 1440,60 L1440,0 L0,0 Z" />
        </svg>
    </div>
</nav>
