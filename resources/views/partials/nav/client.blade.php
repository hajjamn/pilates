<nav class="navbar navbar-fixed-height my-bg-brand-700 shadow-sm border-bottom">
    <div class="container py-2 w-100">
        <div class="row align-items-center w-100">
            {{-- SX: Logo -> dashboard --}}
            <div class="col-4 col-md-3 d-flex align-items-center">
                <a class="navbar-brand d-inline-flex align-items-center" href="{{ route('client.dashboard') }}">
                    <span class="brand-logo-circle">
                        <img src="{{ Vite::asset('resources/img/logo-no-space.png') }}" alt="Logo">
                    </span>
                </a>
            </div>

            {{-- CENTRO: Titolo + lezioni rimaste + link calendario --}}
            <div class="col-4 col-md-6 text-center">
                <div class="lh-sm">
                    <div class="fw-semibold">@yield('page-title', 'Homepage')</div>
                    <div class="text-muted small">
                        Lezioni rimaste: {{ auth()->user()->remaining_package_lessons ?? '—' }}
                    </div>
                </div>
            </div>

            {{-- DX: Hamburger -> dropdown utente --}}
            <div class="col-4 col-md-3 d-flex justify-content-end">
                <div class="dropdown">
                    <button class="btn btn-link text-dark p-0 border-0" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="Apri menu utente">
                        <i class="fa-solid fa-bars fa-lg"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            {{ auth()->user()->full_name ?? auth()->user()->email }}
                        </li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i
                                    class="fa-solid fa-user me-2"></i> Profilo</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('client.user-packages.index') }}"><i
                                    class="fa-solid fa-box-open me-2"></i> Pacchetti</a>
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
</nav>
