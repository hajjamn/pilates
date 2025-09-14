@extends('layouts.app')

@section('page-title', 'Profilo')

@section('content')
    <div class="container" style="max-width: 900px;">
        {{-- Stato verifica email (ridondante rispetto al banner, ma utile qui) --}}
        @if (!$user->hasVerifiedEmail())
            <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
                <div>
                    <strong>Email non verificata.</strong>
                    Controlla la posta o reinvia il link.
                </div>
                <form method="POST" action="{{ route('verification.send') }}" class="mb-0">
                    @csrf
                    <button class="btn btn-sm btn-warning" type="submit">Invia di nuovo</button>
                </form>
            </div>
        @endif

        <div class="row g-4">
            {{-- Dati account --}}
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">Dati profilo</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" name="first_name"
                                    class="form-control @error('first_name') is-invalid @enderror"
                                    value="{{ old('first_name', $user->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cognome</label>
                                <input type="text" name="last_name"
                                    class="form-control @error('last_name') is-invalid @enderror"
                                    value="{{ old('last_name', $user->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email
                                    @if ($user->hasVerifiedEmail())
                                        <span class="badge bg-success">verificata</span>
                                    @else
                                        <span class="badge bg-warning text-dark">non verificata</span>
                                    @endif
                                </label>
                                <input type="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}" required autocomplete="email">
                                <div class="form-text">
                                    Se cambi email, dovrai verificarla di nuovo.
                                </div>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Telefono (opzionale)</label>
                                <input type="tel" name="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $user->phone) }}" inputmode="tel" placeholder="+39 333 1234567">
                                <div class="form-text">Usa il formato internazionale (E.164), es. <strong>+39…</strong>
                                </div>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Data di nascita (opz.)</label>
                                <input type="date" name="birth_date"
                                    class="form-control @error('birth_date') is-invalid @enderror"
                                    value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}"
                                    max="{{ now()->toDateString() }}">
                                @error('birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Salva modifiche</button>
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Annulla</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Sicurezza: cambio password + elimina account --}}
            <div class="col-12 col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">Cambia password</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Password attuale</label>
                                <input type="password" name="current_password"
                                    class="form-control @error('current_password') is-invalid @enderror" required
                                    autocomplete="current-password">
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nuova password</label>
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" required
                                    autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Conferma nuova password</label>
                                <input type="password" name="password_confirmation" class="form-control" required
                                    autocomplete="new-password">
                            </div>

                            <button class="btn btn-secondary" type="submit">Aggiorna password</button>
                        </form>
                    </div>
                </div>

                <div class="card border-danger">
                    <div class="card-header text-danger">Elimina account</div>
                    <div class="card-body">
                        <p class="text-muted">Questa azione disattiva il tuo account (eliminazione reversibile).</p>
                        <form method="POST" action="{{ route('profile.destroy') }}"
                            onsubmit="return confirm('Sei sicuro di voler eliminare l’account?');">
                            @csrf
                            @method('DELETE')
                            <div class="mb-3">
                                <label class="form-label">Conferma con password</label>
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-outline-danger">Elimina account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
