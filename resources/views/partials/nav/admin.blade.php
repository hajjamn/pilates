<nav class="navbar navbar-dark navbar-expand-md navbar-fixed-height my-bg-brand-700 shadow-sm border-bottom">
    <div class="container">
        {{-- Logo --}}
        <a class="navbar-brand d-flex align-items-center" href="{{ route('admin.dashboard') }}">
            <span class="brand-logo-circle">
                <img src="{{ Vite::asset('resources/img/logo-no-space.png') }}" alt="Logo">
            </span>
        </a>

        {{-- Hamburger mobile --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin"
            aria-controls="navAdmin" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navAdmin">
            {{-- SX: voci amministrative --}}
            <ul class="navbar-nav me-auto">
                <li>
                    <hr class="dropdown-divider d-md-none">
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('calendar.lessons.index') }}">
                        <i class="fa-solid fa-calendar-days me-2"></i> Calendario lezioni
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider d-md-none">
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('admin.availability.index') }}">
                        <i class="fa-solid fa-calendar-check me-2"></i> Disponibilità
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider d-md-none">
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('operator.operators.index') }}">
                        <i class="fa-solid fa-users me-2"></i> Operatori
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider d-md-none">
                </li>

                @if (Route::has('admin.users.index'))
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('admin.users.index') }}">
                            <i class="fa-solid fa-user-group me-2"></i> Clienti
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider d-md-none">
                    </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('admin.rooms.index') }}">
                        <i class="fa-solid fa-door-open me-2"></i> Sale
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider d-md-none">
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('admin.machines.index') }}">
                        <i class="fa-solid fa-dumbbell me-2"></i> Macchine
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider d-md-none">
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('admin.packages.index') }}">
                        <i class="fa-solid fa-box me-2"></i> Pacchetti
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider d-md-none">
                </li>
            </ul>

            {{-- DX: utente --}}
            <ul class="navbar-nav ms-auto">
                {{-- Mobile/Tablet: dropdown dentro collapse --}}
                <li class="nav-item dropdown d-md-none">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button"
                        data-bs-toggle="dropdown">
                        {{ auth()->user()->full_name ?? auth()->user()->email }}
                    </a>
                    <ul class="dropdown-menu">
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
                </li>

                {{-- Desktop: dropdown a destra --}}
                <li class="nav-item dropdown d-none d-md-block">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button"
                        data-bs-toggle="dropdown">
                        {{ auth()->user()->full_name ?? auth()->user()->email }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
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
                </li>
            </ul>
        </div>
    </div>
</nav>
