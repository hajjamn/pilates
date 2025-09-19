@extends('layouts.app')

@section('page-title', 'Dettaglio lezione')

@section('content')
    @php
        $isCanceled = (bool) $lesson->canceled;
        $statusLabel = $isCanceled ? 'Annullata' : ($lesson->starts_at?->isPast() ? 'Conclusa' : 'Attiva');
        $statusClass = $isCanceled
            ? 'bg-danger-subtle text-danger fw-semibold'
            : ($lesson->starts_at?->isPast()
                ? 'bg-secondary-subtle text-secondary fw-semibold'
                : 'bg-success-subtle text-success fw-semibold');

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

                @if ($mode === 'operator' && (int) auth()->id() === (int) $lesson->operator_id)
                    @if (!$isCanceled)
                        <form method="POST" action="{{ route('lessons.cancel', $lesson) }}"
                            class="d-inline cancel-lesson-form">
                            @csrf
                            <button type="button" class="btn btn-sm btn-danger open-cancel-modal"
                                data-lesson-id="{{ $lesson->id }}"
                                data-lesson-time="{{ $lesson->starts_at?->translatedFormat('H:i — d MMM') }}"
                                data-lesson-room="{{ $roomName }}" data-lesson-operator="{{ $operatorName }}">
                                Annulla
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('lessons.uncancel', $lesson) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Ripristina</button>
                        </form>
                    @endif
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
                            <thead class="small text-muted">
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
                                        <th>Pacchetto</th>
                                        <th>Pagato</th>
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
                                        <td class="fw-semibold">{{ $fullName }}</td>
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
                                            <td class="small text-center">
                                                @if ($isOperatorOnly)
                                                    @if ($b->user_package_id)
                                                        <span class="badge text-bg-light">Usato</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                @else
                                                    @if ($pkgLabel)
                                                        <span class="badge text-bg-light">{{ $pkgLabel }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php $btnStyle = $paid ? 'btn-outline-success' : 'btn-outline-danger'; @endphp

                                                <button
                                                    class="btn btn-sm {{ $btnStyle }} toggle-paid {{ $paidLocked ? 'is-locked opacity-75' : '' }}"
                                                    data-url="{{ route('bookings.togglePaid', $b) }}"
                                                    @if ($paidLocked) disabled
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Bloccato: pagamento tramite pacchetto" @endif
                                                    type="button">

                                                    @if ($paidLocked)
                                                        <i class="fa-solid fa-lock"></i>
                                                        <span class="visually-hidden">Pagamento bloccato (pacchetto)</span>
                                                    @else
                                                        {{ $paid ? '✓' : 'X' }}
                                                    @endif
                                                </button>
                                            </td>

                                            <td class="text-center">
                                                <button
                                                    class="btn btn-sm {{ $attended ? 'btn-outline-success' : 'btn-outline-danger' }} toggle-attended"
                                                    data-url="{{ route('bookings.toggleAttended', $b) }}" type="button">
                                                    {{ $attended ? '✓' : 'X' }}
                                                </button>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('bookings.cancel', $b) }}"
                                                    class="d-inline remove-booking-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm">Rimuovi</button>
                                                </form>
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
            $isPast = $lesson->starts_at->isPast();
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
                    @elseif ($isPast)
                        <div class="alert alert-secondary mb-3">
                            La lezione è già conclusa: puoi comunque aggiungere un partecipante, ti verrà chiesta conferma.
                        </div>
                    @endif

                    <form class="add-booking-form" data-action="{{ route('bookings.store', $lesson) }}"
                        {{-- flag per conferma se lezione conclusa --}} {{ $isPast ? 'data-is-past=1' : '' }}>
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

                            @if ($mode === 'admin')
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">Pacchetto (opz.)</label>
                                    <select class="form-select form-select-sm package-select" name="user_package_id"
                                        disabled>
                                        <option value="">— Nessun pacchetto —</option>
                                    </select>
                                    <div class="form-text small text-muted">
                                        Si abilita dopo aver scelto il cliente.
                                    </div>
                                </div>
                            @endif

                            <div class="col-6 col-md-3 text-end">
                                <button class="btn btn-sm my-btn-brand-primary w-100 add-booking-btn"
                                    {{ $isCanceled || $atCapacity ? 'disabled' : '' }}>
                                    Aggiungi
                                </button>
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
                            <li><strong>Quando:</strong> <span id="cancel-lesson-time">—</span></li>
                            <li><strong>Sala:</strong> <span id="cancel-lesson-room">—</span></li>
                            <li><strong>Operatore:</strong> <span id="cancel-lesson-operator">—</span></li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                        <button type="button" class="btn btn-danger" id="confirmLessonCancelBtn">Annulla lezione</button>
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
                        <button type="button" class="btn btn-danger" id="confirmAddPastBtn">Aggiungi comunque</button>
                    </div>
                </div>
            </div>
        </div>

    @endonce



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
                        },
                        credentials: 'same-origin',
                    };
                    return fetch(url, Object.assign(base, opts)).then(async r => {
                        if (!r.ok) {
                            let data;
                            try {
                                data = await r.json();
                            } catch (e) {}
                            const err = new Error('Request failed');
                            err.response = data || {};
                            err.status = r.status;
                            throw err;
                        }
                        return r.json();
                    });
                }

                function setToggleBtnState(btn, isOn) {
                    // testo
                    btn.textContent = isOn ? '✓' : 'X';
                    // classi visive
                    btn.classList.remove('btn-outline-success', 'btn-outline-danger', 'btn-success', 'btn-danger');
                    btn.classList.add(isOn ? 'btn-outline-success' : 'btn-outline-danger');
                }

                document.addEventListener('click', async (e) => {
                    const paidBtn = e.target.closest('.toggle-paid');
                    const attBtn = e.target.closest('.toggle-attended');
                    const contBtn = e.target.closest('.toggle-contacted');

                    if (!paidBtn && !attBtn && !contBtn) return;
                    e.preventDefault();

                    const btn = paidBtn || attBtn || contBtn;

                    if (btn.classList.contains('is-locked') || btn.disabled) return;

                    const url = btn.dataset.url;
                    btn.disabled = true;

                    try {
                        const res = await fetchJSON(url, {
                            method: 'POST'
                        });

                        if (paidBtn) {
                            const paid = !!res?.booking?.paid;
                            setToggleBtnState(btn, paid);
                        } else if (attBtn) {
                            const attended = !!res?.booking?.attended;
                            setToggleBtnState(btn, attended);
                        } else if (contBtn) {
                            // opzionale: diamo anche qui feedback verde/rosso
                            const contacted = !!res?.booking?.contacted;
                            setToggleBtnState(btn, contacted);
                        }
                    } catch (err) {
                        alert(err.response?.message || 'Errore nell’aggiornamento');
                    } finally {
                        btn.disabled = false;
                    }
                });
            })();
        </script>


        <script>
            (function() {
                let currentCancelForm = null;

                const cancelModalEl = document.getElementById('lessonCancelModal');
                const cancelModal = cancelModalEl && (window.bootstrap ?
                    new window.bootstrap.Modal(cancelModalEl) :
                    null);

                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('.open-cancel-modal');
                    if (!btn) return;

                    e.preventDefault();
                    currentCancelForm = btn.closest('form');

                    // Riempie i dettagli nella modale
                    const tEl = document.getElementById('cancel-lesson-time');
                    const rEl = document.getElementById('cancel-lesson-room');
                    const oEl = document.getElementById('cancel-lesson-operator');
                    if (tEl) tEl.textContent = btn.dataset.lessonTime || '—';
                    if (rEl) rEl.textContent = btn.dataset.lessonRoom || '—';
                    if (oEl) oEl.textContent = btn.dataset.lessonOperator || '—';

                    if (cancelModal) {
                        cancelModal.show();
                    } else if (confirm('Annullare questa lezione?')) {
                        currentCancelForm?.submit();
                    }
                });

                document.getElementById('confirmLessonCancelBtn')?.addEventListener('click', () => {
                    if (currentCancelForm) {
                        currentCancelForm.submit();
                    }
                });
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

                // --- Conferma aggiunta se lezione conclusa ---
                let pendingPastForm = null;
                const pastModalEl = document.getElementById('addPastConfirmModal');
                const pastModal = pastModalEl && (window.bootstrap ? new bootstrap.Modal(pastModalEl) : null);

                document.getElementById('confirmAddPastBtn')?.addEventListener('click', () => {
                    if (pendingPastForm) {
                        pendingPastForm.dataset.confirmed = '1';
                        pastModal?.hide();
                        pendingPastForm.requestSubmit(); // rilancia submit ora confermato
                        pendingPastForm = null;
                    }
                });

                // --- Autocomplete + add booking (solo se esiste il form) ---
                document.querySelectorAll('.add-booking-form').forEach(form => {
                    const action = form.dataset.action;
                    const searchInput = form.querySelector('.client-search');
                    const results = form.querySelector('.client-results');
                    const userIdInput = form.querySelector('.client-id');
                    const markPaid = form.querySelector('.mark-paid');
                    const addBtn = form.querySelector('.add-booking-btn');

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

                                    const pkgSelect = form.querySelector(
                                        '.package-select');
                                    if (pkgSelect) {
                                        // Svuota e ripristina placeholder
                                        pkgSelect.innerHTML =
                                            '<option value="">— Nessun pacchetto —</option>';
                                        pkgSelect.disabled = true;

                                        // Aspettiamo che l’endpoint 'clients.search' includa i pacchetti in item.packages
                                        // Ogni package: { id, name, lessons_remaining }
                                        const pkgs = Array.isArray(item.packages) ? item
                                            .packages : [];

                                        if (pkgs.length) {
                                            pkgs.forEach(p => {
                                                const opt = document
                                                    .createElement('option');
                                                opt.value = p.id;
                                                const pName = p.name ?? (p
                                                    .package && p.package
                                                    .name) ?? (
                                                    'Pacchetto #' + p.id);
                                                opt.textContent = p.label || p
                                                    .name || ('Pacchetto #' + p
                                                        .id);

                                                pkgSelect.appendChild(opt);
                                            });
                                            pkgSelect.disabled = false;
                                        }
                                    }
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

                    // Submit "Aggiungi"
                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();

                        // Se la lezione è conclusa, chiedi conferma (una sola volta)
                        if (form.dataset.isPast === '1' && !form.dataset.confirmed) {
                            if (pastModal) {
                                pendingPastForm = form;
                                pastModal.show();
                                return; // attendo conferma modale
                            }
                            // fallback senza Bootstrap
                            if (!confirm('La lezione è già conclusa. Aggiungere comunque?')) return;
                            form.dataset.confirmed = '1';
                        }

                        if (!userIdInput.value) {
                            alert('Seleziona un cliente dall’elenco.');
                            return;
                        }

                        addBtn.disabled = true;
                        try {
                            const payload = new URLSearchParams();
                            payload.set('user_id', userIdInput.value);
                            if (markPaid && markPaid.checked) payload.set('paid', '1');

                            const pkgSelect = form.querySelector('.package-select');
                            if (pkgSelect && pkgSelect.value) {
                                payload.set('user_package_id', pkgSelect.value);
                                payload.set('use_package',
                                    '1'); // <— fondamentale: attiva il consumo credito
                            }

                            await fetchJSON(action, {
                                method: 'POST',
                                body: payload
                            });
                            window.location.reload(); // semplice e robusto
                        } catch (err) {
                            const errors = err.response?.errors;
                            const first = errors ? Object.values(errors)[0][0] : (err.response
                                ?.message || 'Errore');
                            alert(first);
                        } finally {
                            addBtn.disabled = false;
                        }
                    });
                });

                // --- Rimozione iscrizione (senza reload se vuoi) ---
                document.addEventListener('submit', async (e) => {
                    const form = e.target.closest('.remove-booking-form');
                    if (!form) return;
                    e.preventDefault();
                    if (!confirm('Rimuovere questa prenotazione?')) return;
                    try {
                        const r = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: new URLSearchParams(new FormData(form)),
                        });
                        if (r.ok) {
                            form.closest('tr')?.remove();
                            window.location.reload();
                        } else {
                            alert('Errore nella rimozione');
                        }
                    } catch (_) {
                        alert('Errore di rete');
                    }
                });
            })();
        </script>
    @endpush

@endsection
