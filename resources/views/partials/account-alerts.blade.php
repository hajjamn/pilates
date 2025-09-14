<div class="container mt-2">
    @php $u = auth()->user(); @endphp

    {{-- Avviso: email non verificata (evita la pagina di notice per non duplicare) --}}
    @if ($u && !$u->hasVerifiedEmail() && !request()->routeIs('verification.notice'))
        <div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
            <div>
                <strong>Email non verificata.</strong>
                Controlla la posta e segui il link di conferma.
                Se non trovi l’email puoi richiedere un nuovo invio.
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary me-2" href="{{ route('verification.notice') }}">
                    Istruzioni
                </a>
                <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-warning" type="submit">
                        Invia di nuovo
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Avviso: telefono mancante --}}
    @if ($u && empty($u->phone))
        <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
            <div>
                <strong>Manca il numero di telefono.</strong>
                Aggiungilo per facilitare i contatti e le comunicazioni.
            </div>
            <a class="btn btn-sm btn-primary" href="{{ route('profile.edit') }}">
                Completa profilo
            </a>
        </div>
    @endif
</div>
