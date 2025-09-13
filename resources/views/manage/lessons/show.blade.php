@extends('layouts.app')

@section('page-title', 'Dettaglio lezione')

@section('content')
    @php
        $isCanceled = (bool) $lesson->canceled;
        $statusLabel = $isCanceled ? 'Annullata' : ($lesson->starts_at?->isPast() ? 'Conclusa' : 'Attiva');
        $statusStyle = $isCanceled
            ? ['#fee2e2', '#991b1b']
            : ($lesson->starts_at?->isPast()
                ? ['#e5e7eb', '#374151']
                : ['#dcfce7', '#166534']);
        $operatorName =
            $lesson->operator?->full_name ??
            trim(($lesson->operator?->first_name ?? '') . ' ' . ($lesson->operator?->last_name ?? '')) ?:
            $lesson->operator?->email ?? '—';
        $roomName = $lesson->room?->name ?? '—';
    @endphp
    <div class="container" style="max-width:900px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">
                Lezione • {{ $lesson->starts_at?->translatedFormat('d MMMM Y, H:mm') }}
            </h1>

            <div class="d-flex gap-2">
                @if ($mode === 'admin')
                    <a href="{{ route('lessons.edit', $lesson) }}" class="btn btn-primary btn-sm">
                        Modifica
                    </a>
                @else
                    <a href="{{ route('lessons.editLite', $lesson) }}" class="btn btn-primary btn-sm">
                        Modifica
                    </a>
                @endif

                @if ($mode === 'admin' || (auth()->user()?->hasRole('operatore') && (int) auth()->id() === (int) $lesson->operator_id))
                    @if (!$isCanceled)
                        <form method="POST" action="{{ route('lessons.cancel', $lesson) }}"
                            class="d-inline cancel-lesson-form">
                            @csrf
                            <button type="button" class="btn btn-outline-danger btn-sm open-cancel-modal"
                                data-lesson-id="{{ $lesson->id }}"
                                data-lesson-time="{{ $lesson->starts_at?->translatedFormat('H:i — d MMM') }}"
                                data-lesson-room="{{ $roomName }}"
                                data-lesson-operator="{{ $operatorName }}">Annulla</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('lessons.uncancel', $lesson) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-success btn-sm">Ripristina</button>
                        </form>
                    @endif
                @endif

                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Indietro</a>
            </div>

        </div>

        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="small text-muted">Stato</div>
                    <span class="badge"
                        style="background:{{ $statusStyle[0] }}; color:{{ $statusStyle[1] }}; font-weight:600;">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div>
                    <div class="small text-muted">Sala</div>
                    <div class="fw-semibold">{{ $roomName }}</div>
                </div>
                <div>
                    <div class="small text-muted">Operatore</div>
                    <div class="fw-semibold">{{ $operatorName }}</div>
                </div>
                <div>
                    <div class="small text-muted">Capienza</div>
                    <div class="fw-semibold">{{ $lesson->clients_count ?? 0 }} / {{ $lesson->max_clients }}</div>
                </div>
                <div>
                    <div class="small text-muted">Manual override</div>
                    <div class="fw-semibold">{{ $lesson->manual_override ? 'Sì' : 'No' }}</div>
                </div>
            </div>
        </div>

        {{-- Iscritti --}}
        <div class="card mb-4">
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
                                        $pkgLabel = $pkg?->package?->name
                                            ? $pkg->package->name . ' (rimasti: ' . $pkg->lessons_remaining . ')'
                                            : null;

                                        $paid = (bool) ($b->paid ?? false);
                                        $attended = (bool) ($b->attended ?? false);
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $fullName }}</td>
                                        <td class="small">
                                            @if ($u?->email)
                                                <div>
                                                    <a href="mailto:{{ $u->email }}"> <i
                                                            class="fa-solid fa-envelope me-1"></i> {{ $u->email }}</a>
                                                </div>
                                            @endif
                                            @if ($u->phone)
                                                <div>
                                                    @if ($waLink)
                                                        <a href="{{ $waLink }}" target="_blank" rel="noopener"
                                                            class="text-decoration-none">
                                                            <i class="fa-brands fa-whatsapp me-1"></i>
                                                            {{ $u->phone }}
                                                        </a>
                                                    @else
                                                        <span>{{ $u->phone }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @unless ($u?->email || $u?->phone)
                                                <span class="text-muted">—</span>
                                            @endunless
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
                                            <td class="small">
                                                @if ($pkgLabel)
                                                    <span class="badge text-bg-light">{{ $pkgLabel }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-primary btn-sm toggle-paid"
                                                    data-url="{{ route('bookings.togglePaid', $b) }}" type="button">
                                                    {{ $paid ? '✓' : 'X' }}
                                                </button>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-primary btn-sm toggle-attended"
                                                    data-url="{{ route('bookings.toggleAttended', $b) }}">
                                                    {{ $attended ? '✓' : 'X' }}
                                                </button>
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
    </div>

    @once
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

                document.addEventListener('click', async (e) => {
                    const paidBtn = e.target.closest('.toggle-paid');
                    const attBtn = e.target.closest('.toggle-attended');
                    const contBtn = e.target.closest('.toggle-contacted');

                    if (!paidBtn && !attBtn && !contBtn) return;
                    e.preventDefault();

                    const btn = paidBtn || attBtn || contBtn;
                    const url = btn.dataset.url;
                    btn.disabled = true;

                    try {
                        const res = await fetchJSON(url, {
                            method: 'POST'
                        });
                        if (paidBtn) {
                            const paid = !!res?.booking?.paid;
                            btn.textContent = paid ? '✓' : 'X';
                        } else if (attBtn) {
                            const attended = !!res?.booking?.attended;
                            btn.textContent = attended ? '✓' : 'X';
                        } else if (contBtn) {
                            const contacted = !!res?.booking?.contacted;
                            btn.textContent = contacted ? '✓' : 'X';
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
    @endpush

@endsection
