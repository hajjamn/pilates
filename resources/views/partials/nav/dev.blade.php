{{-- resources/views/partials/nav/dev_curved.blade.php --}}
<header class="dev-hero position-relative text-white">
    <div class="container py-3 w-100">
        <div class="row align-items-center w-100">

            {{-- SX: Logo -> dashboard --}}
            <div class="col-4 col-md-3 d-flex align-items-center">
                <a class="navbar-brand d-inline-flex align-items-center" href="{{ route('client.dashboard') }}">
                    <img src="{{ Vite::asset('resources/img/logo.png') }}" alt="Logo" style="height: 48px;">
                </a>
            </div>

            {{-- CENTRO: Titolo + lezioni rimaste --}}
            <div class="col-4 col-md-6 text-center">
                <div class="lh-sm">
                    <div class="fw-semibold h5 mb-0">@yield('page-title', 'Homepage')</div>
                    <div class="small opacity-90">
                        Lezioni rimaste: {{ auth()->user()->remaining_package_lessons ?? '—' }}
                    </div>
                </div>
            </div>

            {{-- DX: Hamburger -> dropdown utente --}}
            <div class="col-4 col-md-3 d-flex justify-content-end">
                <div class="dropdown">
                    <button class="btn btn-link p-0 border-0 text-white" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="Apri menu utente">
                        <i class="fa-solid fa-bars fa-lg"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            {{ auth()->user()->full_name ?? 'Ospite' }}
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fa-solid fa-user me-2"></i> Profilo
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    {{-- Onda/curva inferiore: verrà colorata via SCSS --}}
    <svg class="dev-hero-wave position-absolute start-0 end-0 bottom-0" viewBox="0 0 1440 120"
        preserveAspectRatio="none" aria-hidden="true">
        <path class="wave-path" d="M0,32 C240,96 480,0 720,32 C960,64 1200,128 1440,64 L1440,120 L0,120 Z"></path>
    </svg>
</header>
