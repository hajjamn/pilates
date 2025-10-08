@extends('layouts.app')

@section('page-title', 'Dettaglio lezione')

@section('content')
    @php
        $isCanceled = (bool) $lesson->canceled;

        $now = now();
        $duration = 60; // se hai un campo in DB: $lesson->duration_minutes ?? 60
        $startsAt = $lesson->starts_at;
        $endsAt = $lesson->starts_at?->copy()->addMinutes($duration);

        $hasStarted = $startsAt?->lte($now);
        $notEnded = $endsAt?->gte($now);
        $isLive = !$isCanceled && $hasStarted && $notEnded;
        $isEnded = $endsAt?->lt($now); // <-- usa questo al posto di isPast per coerenza

        $statusLabel = $isCanceled ? 'Annullata' : ($isLive ? 'In corso' : ($isEnded ? 'Conclusa' : 'Attiva'));

        $statusClass = $isCanceled
            ? 'bg-danger-subtle text-danger fw-semibold'
            : ($isLive
                ? 'bg-info-subtle text-info fw-semibold'
                : ($isEnded
                    ? 'bg-secondary-subtle text-secondary fw-semibold'
                    : 'bg-success-subtle text-success fw-semibold'));

        $operatorName =
            $lesson->operator?->full_name ??
            trim(($lesson->operator?->first_name ?? '') . ' ' . ($lesson->operator?->last_name ?? '')) ?:
            $lesson->operator?->email ?? '—';

        $roomName = $lesson->room?->name ?? '—';
        $isOperatorOnly = auth()->user()?->hasRole('operatore') && !auth()->user()?->hasRole('admin');
    @endphp



    <div class="container my-4 operator-lesson-show">
        <h2 class="h4 text-center mb-4">Dettaglio Lezione</h2>

        {{-- Header titolo + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h3 class="h5 mb-2">
                {{ ucfirst($lesson->starts_at->translatedFormat('l d')) }}
                {{ ucfirst($lesson->starts_at->translatedFormat('F')) }}
                - {{ $lesson->starts_at->format('H:i') }}
            </h3>
            <div class="d-flex gap-2">
                {{-- Pulsanti azione --}}
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary"><i class="fa-solid fa-chevron-left"></i>
                    Indietro</a>
                @if ($mode === 'operator')
                    <a href="{{ route('lessons.editLite', $lesson) }}" class="btn btn-sm my-btn-brand-primary">Modifica</a>
                @elseif ($mode === 'admin')
                    <a href="{{ route('lessons.edit', $lesson) }}" class="btn btn-sm my-btn-brand-primary">
                        Modifica (admin)
                    </a>
                @endif

                @if (!$isCanceled && (($mode === 'operator' && (int) auth()->id() === (int) $lesson->operator_id) || $mode === 'admin'))
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                        data-bs-target="#lessonCancelModal">
                        Annulla
                    </button>
                @elseif ($mode === 'operator' && (int) auth()->id() === (int) $lesson->operator_id)
                    <form method="POST" action="{{ route('lessons.uncancel', $lesson) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-success">Ripristina</button>
                    </form>
                @elseif ($mode === 'admin')
                    <form method="POST" action="{{ route('lessons.uncancel', $lesson) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-success">Ripristina</button>
                    </form>
                @endif


            </div>
        </div>

        {{-- Card dettagli lezione --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body row g-3 small">
                <div class="col-6 col-md-4">
                    <div class="text-muted">Stato</div>
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">Sala</div>
                    <div class="fw-semibold">{{ $roomName }}</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">Operatore</div>
                    <div class="fw-semibold">{{ $operatorName }}</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">Capienza</div>
                    <div class="fw-semibold">{{ $lesson->clients_count ?? 0 }} / {{ $lesson->max_clients }}</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">Manual override</div>
                    <div class="fw-semibold">{{ $lesson->manual_override ? 'Sì' : 'No' }}</div>
                </div>
            </div>
        </div>

        {{-- Card iscritti --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <strong>Iscritti</strong>
            </div>
            <div class="card-body p-0">
                @php $bookings = $lesson->lessonUsers ?? collect(); @endphp
                @if ($bookings->isEmpty())
                    <div class="p-3 text-muted">Nessun iscritto.</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="small text-muted text-center">
                                @if ($isCanceled)
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Contatti</th>
                                        <th>Contattato</th>
                                    </tr>
                                @else
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Contatti</th>
                                        <th>Pagamento</th>
                                        <th>Presenza</th>
                                        <th class="text-end">Azioni</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @foreach ($bookings as $b)
                                    @php
                                        $u = $b->user;
                                        $fullName =
                                            trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?:
                                            $u->email ?? 'Utente #' . $u->id;

                                        $subject = 'Lezione ' . $lesson->starts_at->format('d/m H:i');
                                        $bodyTxt =
                                            'Ciao ' .
                                            ($u->first_name ?? '') .
                                            ",\nla lezione del " .
                                            $lesson->starts_at->format('d/m H:i') .
                                            '.';
                                        $mailto = $u->email
                                            ? 'mailto:' .
                                                $u->email .
                                                '?subject=' .
                                                rawurlencode($subject) .
                                                '&body=' .
                                                rawurlencode($bodyTxt)
                                            : null;

                                        $e164 = $u->phone;
                                        $waDigits = $e164 ? preg_replace('/\D+/', '', $e164) : null;
                                        $waLink = $waDigits
                                            ? 'https://wa.me/' . $waDigits . '?text=' . rawurlencode($bodyTxt)
                                            : null;

                                        $pkg = $b->userPackage;
                                        $pkgLabel = $pkg?->package?->name ?: null;

                                        $paid = (bool) ($b->paid ?? false);
                                        $attended = (bool) ($b->attended ?? false);
                                        $paidLocked =
                                            $b->user_package_id !== null &&
                                            auth()->user()?->hasRole('operatore') &&
                                            !auth()->user()?->hasRole('admin');
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            @php
                                                // Red flag SE: prenotazione SENZA pacchetto, ma l'utente ha (almeno) un pacchetto non soft-deleted
$hasActivePackages = $u->relationLoaded('packages')
    ? $u->packages->isNotEmpty()
    : $u->packages()->whereNull('deleted_at')->exists(); // fallback se mancasse eager-load (non dovrebbe)
                                                $showRedFlag = is_null($b->user_package_id) && $hasActivePackages;
                                            @endphp

                                            @if ($showRedFlag)
                                                <span class="badge bg-danger align-middle me-1" data-bs-toggle="tooltip"
                                                    title="Prenotazione senza pacchetto, ma l’utente ha pacchetti">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                </span>
                                            @endif

                                            @if ($mode === 'admin')
                                                <a href="{{ route('admin.users.show', $u) }}" class="text-decoration-none">
                                                    {{ $fullName }}
                                                </a>
                                            @else
                                                {{ $fullName }}
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <div class="dropdown d-inline-block" data-bs-display="static">
                                                <button class="btn btn-link btn-sm p-0" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-solid fa-address-card fa-lg"></i>
                                                    <span class="visually-hidden">Contatti</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if ($u?->email)
                                                        <li>
                                                            <a class="dropdown-item text-primary"
                                                                href="mailto:{{ $u->email }}">
                                                                <i
                                                                    class="fa-solid fa-envelope me-2"></i>{{ $u->email }}
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if ($waLink)
                                                        <li>
                                                            <a class="dropdown-item text-whatsapp"
                                                                href="{{ $waLink }}" target="_blank" rel="noopener">
                                                                <i
                                                                    class="fa-brands fa-whatsapp me-2"></i>{{ $u->phone }}
                                                            </a>
                                                        </li>
                                                    @elseif ($u->phone)
                                                        <li>
                                                            <span class="dropdown-item-text">
                                                                <i class="fa-solid fa-phone me-2"></i>{{ $u->phone }}
                                                            </span>
                                                        </li>
                                                    @endif

                                                    @unless ($u?->email || $u?->phone)
                                                        <li>
                                                            <span class="dropdown-item-text text-muted">Nessun contatto</span>
                                                        </li>
                                                    @endunless
                                                </ul>
                                            </div>
                                        </td>

                                        @if ($isCanceled)
                                            {{-- SOLO quando annullata: bottone Contattato --}}
                                            <td>
                                                <button type="button"
                                                    class="btn btn-outline-secondary btn-sm toggle-contacted"
                                                    data-url="{{ route('bookings.toggleContacted', $b) }}">
                                                    {{ $b->contacted ? '✓' : 'X' }}
                                                </button>
                                            </td>
                                        @else
                                            {{-- Vista normale: Pacchetto + Pagato + Presenza con toggle --}}
                                            <td class="text-center">
                                                @php
                                                    $hasPackage = $b->user_package_id !== null;
                                                @endphp

                                                @if ($hasPackage)
                                                    {{-- Caso con pacchetto: mostra solo il badge, NESSUN toggle --}}
                                                    @if ($isOperatorOnly)
                                                        <span class="badge text-bg-light">Pacchetto</span>
                                                    @else
                                                        @if ($pkgLabel)
                                                            <span class="badge text-bg-light">{{ $pkgLabel }}</span>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    @endif
                                                @else
                                                    {{-- Caso senza pacchetto: mostra il toggle "Pagato" come prima --}}
                                                    @php $btnStyle = $paid ? 'btn-outline-success' : 'btn-outline-danger'; @endphp
                                                    <button class="btn btn-sm {{ $btnStyle }} toggle-paid"
                                                        data-url="{{ route('bookings.togglePaid', $b) }}"
                                                        data-paid="{{ $paid ? 1 : 0 }}"
                                                        data-operator-id="{{ (int) $lesson->operator_id }}"
                                                        data-admin-id="{{ (int) auth()->id() }}" type="button">
                                                        {{ $paid ? '✓' : 'X' }}
                                                    </button>
                                                @endif
                                            </td>

                                            <td class="text-center">
                                                <button
                                                    class="btn btn-sm {{ $attended ? 'btn-outline-success' : 'btn-outline-danger' }} toggle-attended"
                                                    data-url="{{ route('bookings.toggleAttended', $b) }}" type="button">
                                                    {{ $attended ? '✓' : 'X' }}
                                                </button>
                                            </td>
                                            <td class="text-end">
                                                @if ($isEnded && $b->user_package_id && $b->counted)
                                                    {{-- Caso A: lezione conclusa + pacchetto usato + counted => Apri modale Bootstrap --}}
                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#noRefundModal-{{ $b->id }}">
                                                        Rimuovi
                                                    </button>

                                                    {{-- Modale per QUESTA prenotazione --}}
                                                    <div class="modal fade text-start"
                                                        id="noRefundModal-{{ $b->id }}" tabindex="-1"
                                                        aria-labelledby="noRefundLabel-{{ $b->id }}"
                                                        aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title"
                                                                        id="noRefundLabel-{{ $b->id }}">Attenzione
                                                                    </h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"
                                                                        aria-label="Chiudi"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p class="mb-0">
                                                                        <strong>ATTENZIONE:</strong> cancellando questa
                                                                        prenotazione, dato che la lezione si è già svolta,
                                                                        il credito nel pacchetto <strong>NON</strong> verrà
                                                                        rimborsato.
                                                                        Se si tratta di un errore contatta l'amministratore
                                                                        per rimborsare manualmente il credito.
                                                                        <br><br>
                                                                        Vuoi comunque cancellare la prenotazione?
                                                                    </p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button"
                                                                        class="btn btn-outline-secondary"
                                                                        data-bs-dismiss="modal">Annulla</button>
                                                                    <form method="POST"
                                                                        action="{{ route('bookings.cancel', $b) }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="btn btn-danger">Cancella
                                                                            comunque</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    {{-- Caso B: comportamento attuale (submit diretto, nessun modale) --}}
                                                    <form method="POST" action="{{ route('bookings.cancel', $b) }}"
                                                        class="d-inline remove-booking-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-outline-danger btn-sm">Rimuovi</button>
                                                    </form>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Aggiunta Clienti --}}
        @php
            $isOperatorOwner = $mode === 'operator' && (int) auth()->id() === (int) $lesson->operator_id;
            $atCapacity = ($lesson->clients_count ?? 0) >= (int) $lesson->max_clients;
        @endphp

        @if ($isOperatorOwner || $mode === 'admin')
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <strong>Aggiungi cliente</strong>
                </div>
                <div class="card-body">

                    @if ($isCanceled)
                        <div class="alert alert-warning mb-0">Lezione annullata: non è possibile aggiungere iscritti.</div>
                    @elseif ($atCapacity)
                        <div class="alert alert-warning mb-3">Capienza massima raggiunta ({{ $lesson->max_clients }}).
                        </div>
                    @elseif ($isEnded)
                        <div class="alert alert-secondary mb-3">
                            La lezione è già conclusa: puoi comunque aggiungere un partecipante, ti verrà chiesta conferma.
                        </div>
                    @endif

                    <form id="addBookingForm-{{ $lesson->id }}" class="add-booking-form" method="POST"
                        action="{{ route('bookings.store', $lesson) }}">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-6 position-relative">
                                <label class="form-label small">Cerca cliente</label>
                                <input type="text" class="form-control form-control-sm client-search"
                                    placeholder="Nome, email o telefono"
                                    {{ $isCanceled || $atCapacity ? 'disabled' : '' }}>
                                <input type="hidden" name="user_id" class="client-id">
                                <div class="list-group position-absolute shadow-sm w-100 d-none client-results"
                                    style="z-index:20; max-height:240px; overflow:auto;"></div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input mark-paid" type="checkbox" value="1"
                                        id="markPaid" {{ $isCanceled || $atCapacity ? 'disabled' : '' }}>
                                    <label class="form-check-label small" for="markPaid">Segna pagato</label>
                                </div>
                            </div>

                            <div class="col-6 col-md-3 text-end">
                                @if ($isEnded)
                                    <button type="button" class="btn btn-sm my-btn-brand-primary w-100"
                                        data-bs-toggle="modal" data-bs-target="#addPastConfirmModal">
                                        Aggiungi
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-sm my-btn-brand-primary w-100 add-booking-btn">
                                        Aggiungi
                                    </button>
                                @endif
                            </div>

                        </div>

                        {{-- NB: per gli operatori niente pacchetti --}}
                    </form>
                </div>
            </div>
        @endif

    </div>

    @once
        {{-- Modale Cancellazione --}}
        <div class="modal fade" id="lessonCancelModal" tabindex="-1" aria-labelledby="lessonCancelModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="lessonCancelModalLabel">Conferma annullamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        <p>Sei sicuro di voler annullare questa lezione?</p>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li><strong>Quando:</strong> {{ $lesson->starts_at?->translatedFormat('H:i — d MMM') }}</li>
                            <li><strong>Sala:</strong> {{ $roomName }}</li>
                            <li><strong>Operatore:</strong> {{ $operatorName }}</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                        <form method="POST" action="{{ route('lessons.cancel', $lesson) }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">Annulla lezione</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        {{-- Modale aggiunta cliente a lezione terminata --}}
        <div class="modal fade" id="addPastConfirmModal" tabindex="-1" aria-labelledby="addPastConfirmModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPastConfirmModalLabel">Conferma aggiunta a lezione conclusa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        La lezione è già conclusa. Sei sicuro di voler aggiungere un partecipante?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-danger" form="addBookingForm-{{ $lesson->id }}">
                            Aggiungi comunque
                        </button>
                    </div>


                </div>
            </div>
        </div>

    @endonce

    @if (auth()->user()?->hasRole('admin'))
        @once
            @php
                $adminUser = auth()->user();
                $adminDisplay =
                    $adminUser?->full_name ??
                    trim(($adminUser?->first_name ?? '') . ' ' . ($adminUser?->last_name ?? '')) ?:
                    $adminUser?->email ?? 'Admin';
            @endphp
            <div class="modal fade" id="paidMetaModal" tabindex="-1" aria-labelledby="paidMetaLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form class="modal-content" id="paidMetaForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="paidMetaLabel">Dettagli pagamento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="endpoint" id="paidEndpoint">
                            <input type="hidden" name="paid_to_user_id" id="paidToUserId">

                            {{-- Radios: visibili solo se l’operatore della lezione è diverso dall’admin --}}
                            <div id="paidRecipientGroup" class="mb-3">
                                <label class="form-label">Pagata a</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recipient" id="paidToAdmin"
                                            value="admin" checked>
                                        <label class="form-check-label" for="paidToAdmin">
                                            Amministratore ({{ $adminDisplay }})
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recipient" id="paidToOperator"
                                            value="operator">
                                        <label class="form-check-label" for="paidToOperator">
                                            Operatore della lezione
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="paidAt" class="form-label">Data/ora pagamento (opz.)</label>
                                <input type="datetime-local" class="form-control" id="paidAt" name="paid_at"
                                    placeholder="YYYY-MM-DDTHH:MM">
                                <div class="form-text">Lascia vuoto per usare l’orario attuale.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                            <button type="submit" class="btn btn-primary">Conferma</button>
                        </div>
                    </form>
                </div>
            </div>
        @endonce
    @endif


    @push('scripts')
        <script>
            (function() {
                const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                function fetchJSON(url, opts = {}) {
                    const base = {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        credentials: 'same-origin',
                    };
                    return fetch(url, Object.assign(base, opts)).then(async r => {
                        let data = null;
                        try {
                            data = await r.json();
                        } catch (_) {}
                        if (!r.ok) {
                            const err = new Error('Request failed');
                            err.response = data || {};
                            err.status = r.status;
                            throw err;
                        }
                        return data;
                    });
                }

                function setToggleBtnState(btn, isOn) {
                    btn.textContent = isOn ? '✓' : 'X';
                    btn.classList.remove('btn-outline-success', 'btn-outline-danger', 'btn-success', 'btn-danger');
                    btn.classList.add(isOn ? 'btn-outline-success' : 'btn-outline-danger');
                    btn.dataset.paid = isOn ? '1' : '0';
                }

                // --- cleanup robusto di backdrop/modal-open ---
                function cleanupModalArtifacts() {
                    // se non c'è nessun .modal.show, rimuovi eventuali backdrop residui e modal-open
                    if (!document.querySelector('.modal.show')) {
                        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('padding-right');
                    }
                }

                // --- apri modal (bootstrap o fallback)
                function openModal(el) {
                    if (!el) return null;
                    if (window.bootstrap && window.bootstrap.Modal) {
                        const inst = window.bootstrap.Modal.getOrCreateInstance(el);
                        inst.show();
                        return inst;
                    }
                    // Fallback vanilla
                    el.style.display = 'block';
                    el.classList.add('show');
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.dataset._manualBackdrop = '1';
                    document.body.appendChild(backdrop);
                    document.body.classList.add('modal-open');
                    return {
                        _isFallback: true,
                        hide() {
                            el.classList.remove('show');
                            el.style.display = 'none';
                            const bd = document.querySelector('.modal-backdrop[data-_manualBackdrop="1"]');
                            bd?.remove();
                            document.body.classList.remove('modal-open');
                        }
                    };
                }

                // --- chiudi modal (bootstrap/fallback) + cleanup
                function closeModal(instance) {
                    if (instance && typeof instance.hide === 'function') {
                        instance.hide();
                        setTimeout(cleanupModalArtifacts, 200);
                    } else if (instance && instance._isFallback) {
                        instance.hide();
                        cleanupModalArtifacts();
                    } else {
                        cleanupModalArtifacts();
                    }
                }

                // sicurezza extra quando Bootstrap chiude un modal
                document.addEventListener('hidden.bs.modal', () => {
                    setTimeout(cleanupModalArtifacts, 150);
                });

                const isAdmin = JSON.parse(@json($mode === 'admin'));

                // Riferimenti modale admin (creati nella Blade)
                let paidModalEl = document.getElementById('paidMetaModal');
                let paidForm = document.getElementById('paidMetaForm');
                let paidAtInput = document.getElementById('paidAt');
                let paidToAdmin = document.getElementById('paidToAdmin');
                let paidToOperator = document.getElementById('paidToOperator');
                let paidEndpoint = document.getElementById('paidEndpoint');
                let paidRecipientGroup = document.getElementById('paidRecipientGroup');

                // istanza corrente (bootstrap o fallback)
                let paidModalInstance = null;
                let pendingBtn = null;

                document.addEventListener('click', async (e) => {
                    const paidBtn = e.target.closest('.toggle-paid');
                    const attBtn = e.target.closest('.toggle-attended');
                    const contBtn = e.target.closest('.toggle-contacted');

                    if (!paidBtn && !attBtn && !contBtn) return;
                    e.preventDefault();

                    const btn = paidBtn || attBtn || contBtn;
                    if (btn.classList.contains('is-locked') || btn.disabled) return;

                    // --- TOGGLE PAGATO ---
                    if (paidBtn) {
                        const endpoint = paidBtn.dataset.url;
                        const currentlyPaid = paidBtn.dataset.paid === '1';

                        // pagato -> non pagato (POST diretto)
                        if (currentlyPaid) {
                            try {
                                btn.disabled = true;
                                const res = await fetchJSON(endpoint, {
                                    method: 'POST',
                                    body: ''
                                });
                                const paid = !!res?.booking?.paid;
                                setToggleBtnState(btn, paid);
                            } catch (err) {
                                alert(err.response?.message || 'Errore nell’aggiornamento pagamento');
                            } finally {
                                btn.disabled = false;
                            }
                            return;
                        }

                        // non pagato -> pagato
                        if (!isAdmin) {
                            // Operatore: POST diretto
                            try {
                                btn.disabled = true;
                                const res = await fetchJSON(endpoint, {
                                    method: 'POST',
                                    body: ''
                                });
                                const paid = !!res?.booking?.paid;
                                setToggleBtnState(btn, paid);
                            } catch (err) {
                                alert(err.response?.message || 'Errore nell’aggiornamento pagamento');
                            } finally {
                                btn.disabled = false;
                            }
                            return;
                        }

                        // Admin: apri modale
                        pendingBtn = paidBtn;
                        if (paidEndpoint) paidEndpoint.value = endpoint;
                        if (paidAtInput) paidAtInput.value = '';

                        const adminId = Number(paidBtn.dataset.adminId || 0);
                        const operatorId = Number(paidBtn.dataset.operatorId || 0);
                        const sameUser = adminId && operatorId && (adminId === operatorId);

                        if (paidRecipientGroup) {
                            if (sameUser) {
                                paidRecipientGroup.classList.add('d-none');
                                if (paidToAdmin) paidToAdmin.checked = true;
                                if (paidToOperator) paidToOperator.checked = false;
                            } else {
                                paidRecipientGroup.classList.remove('d-none');
                                if (paidToAdmin) paidToAdmin.checked = true;
                                if (paidToOperator) paidToOperator.checked = false;
                            }
                        }

                        paidModalInstance = openModal(paidModalEl);
                        return;
                    }

                    // --- TOGGLE PRESENZA / CONTATTATO ---
                    try {
                        btn.disabled = true;
                        const res = await fetchJSON(btn.dataset.url, {
                            method: 'POST',
                            body: ''
                        });
                        if (attBtn) {
                            const attended = !!res?.booking?.attended;
                            setToggleBtnState(btn, attended);
                        } else if (contBtn) {
                            const contacted = !!res?.booking?.contacted;
                            setToggleBtnState(btn, contacted);
                        }
                    } catch (err) {
                        alert(err.response?.message || 'Errore nell’aggiornamento');
                    } finally {
                        btn.disabled = false;
                    }
                });

                // Submit modale (ADMIN)
                if (isAdmin && paidForm) {
                    paidForm.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        if (!pendingBtn) return;

                        const endpoint = paidEndpoint?.value || pendingBtn.dataset.url;
                        const adminId = Number(pendingBtn.dataset.adminId || 0);
                        const operatorId = Number(pendingBtn.dataset.operatorId || 0);

                        const body = new URLSearchParams();

                        // destinatario
                        let toUserId = adminId;
                        if (paidRecipientGroup && !paidRecipientGroup.classList.contains('d-none') &&
                            paidToOperator?.checked) {
                            toUserId = operatorId;
                        }
                        if (toUserId) body.set('paid_to_user_id', String(toUserId));

                        // data/ora opzionale
                        const dt = paidAtInput?.value && paidAtInput.value.trim();
                        if (dt) body.set('paid_at', dt);

                        try {
                            pendingBtn.disabled = true;
                            const res = await fetchJSON(endpoint, {
                                method: 'POST',
                                body: body.toString()
                            });
                            const paid = !!res?.booking?.paid;
                            setToggleBtnState(pendingBtn, paid);
                            closeModal(paidModalInstance); // <<< chiusura + cleanup
                        } catch (err) {
                            alert(err.response?.message || 'Errore nel salvataggio pagamento');
                        } finally {
                            pendingBtn.disabled = false;
                            pendingBtn = null;
                            paidModalInstance = null;
                        }
                    });
                }
            })();
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (window.bootstrap) {
                    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
                }
            });
        </script>

        <script>
            (function() {
                const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                async function fetchJSON(url, opts = {}) {
                    const base = {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    };
                    const r = await fetch(url, Object.assign(base, opts));
                    if (!r.ok) {
                        let data;
                        try {
                            data = await r.json();
                        } catch (_) {}
                        const err = new Error('Request failed');
                        err.response = data || {};
                        err.status = r.status;
                        throw err;
                    }
                    return r.json();
                }


                // --- Autocomplete + add booking (solo se esiste il form) ---
                document.querySelectorAll('.add-booking-form').forEach(form => {
                    const searchInput = form.querySelector('.client-search');
                    const results = form.querySelector('.client-results');
                    const userIdInput = form.querySelector('.client-id');
                    const markPaid = form.querySelector('.mark-paid');

                    if (!searchInput) return;

                    let lastQuery = '';
                    let controller = null;

                    const closeResults = () => {
                        results.classList.add('d-none');
                        results.innerHTML = '';
                    };

                    // Autocomplete
                    searchInput.addEventListener('input', async () => {
                        const q = searchInput.value.trim();
                        userIdInput.value = '';
                        if (q.length < 2) {
                            closeResults();
                            return;
                        }

                        lastQuery = q;
                        if (controller) controller.abort();
                        controller = new AbortController();

                        try {
                            const res = await fetchJSON("{{ route('clients.search') }}?q=" +
                                encodeURIComponent(q), {
                                    signal: controller.signal
                                });
                            const list = res?.data || [];
                            if (q !== lastQuery) return;

                            results.innerHTML = '';
                            list.forEach(item => {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'list-group-item list-group-item-action';
                                btn.innerHTML =
                                    `<strong>${item.name || '—'}</strong><br><small>${item.email || ''} ${item.phone ? ' · ' + item.phone : ''}</small>`;
                                btn.addEventListener('click', () => {
                                    searchInput.value = item.name || item.email || (
                                        'Utente #' + item.id);
                                    userIdInput.value = item.id;
                                    closeResults();
                                });
                                results.appendChild(btn);
                            });
                            results.classList.toggle('d-none', list.length === 0);
                        } catch (_) {
                            /* ignore */
                        }
                    });

                    // Chiudi lista se clicchi fuori
                    document.addEventListener('click', (ev) => {
                        if (!results.contains(ev.target) && ev.target !== searchInput) closeResults();
                    });

                });

            })();
        </script>
    @endpush

@endsection
