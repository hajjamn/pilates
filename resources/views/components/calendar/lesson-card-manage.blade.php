@props([
    'lesson',
    'mode' => 'operator', // 'operator' | 'admin'
])

@php
    $roomName = $lesson->room?->name ?? '—';
    $bookings = $lesson->lessonUsers ?? collect();
    $clientsCount = $bookings->count();
    $max = $lesson->max_clients ?? null;

    $isCanceled = (bool) $lesson->canceled;

    $now = now();
    $hasStarted = $lesson->starts_at?->lte($now);
    $notEnded = $lesson->starts_at?->copy()->addMinutes(60)->gte($now);

    $isLive = !$isCanceled && $hasStarted && $notEnded;
    $isPast = $lesson->starts_at?->copy()->addMinutes(60)->lt($now);

    $statusLabel = $isCanceled ? 'Annullata' : ($isLive ? 'In corso' : ($isPast ? 'Conclusa' : 'Attiva'));

    $statusClass = $isCanceled
        ? 'bg-danger-subtle text-danger'
        : ($isLive
            ? 'bg-info-subtle text-info'
            : ($isPast
                ? 'bg-secondary-subtle text-secondary'
                : 'bg-success-subtle text-success'));

    // per la riga "Giorno": es. "Lun 18 Set"
    $dayLabel = ucfirst($lesson->starts_at?->isoFormat('ddd D MMM'));
    $timeLabel = $lesson->starts_at?->format('H:i');

    // Proprietà per permessi azioni rapide
    $canQuickActions =
        $mode === 'admin' ||
        (auth()->user()?->hasRole('operatore') && (int) auth()->id() === (int) $lesson->operator_id);
@endphp

<div class="card shadow-sm" data-lesson-id="{{ $lesson->id }}">
    {{-- Header compatto --}}
    <div class="card-header bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="fs-5 fw-semibold">{{ $timeLabel }}</div>
                <span class="badge {{ $statusClass }} fw-semibold">{{ $statusLabel }}</span>
            </div>
            <div class="small text-muted">{{ $dayLabel }}</div>
            <div class="small fw-semibold">{{ $roomName }}</div>
            @if ($mode === 'admin')
                <div class="small text-muted">
                    Operatore:
                    {{ optional($lesson->operator)->full_name ??
                    trim((optional($lesson->operator)->first_name ?? '') . ' ' . (optional($lesson->operator)->last_name ?? '')) ?:
                        '—' }}
                </div>
            @endif
        </div>

        <div class="text-end">
            <div class="small text-muted">Iscritti</div>
            <div class="fw-semibold">
                {{ $clientsCount }}@if (!is_null($max))
                    / {{ $max }}
                @endif
                @if (!is_null($max) && $clientsCount > $max)
                    <span class="badge text-bg-warning align-middle ms-1">over</span>
                @endif
            </div>

            {{-- Azioni rapide minimali --}}
            @if ($canQuickActions)
                <div class="mt-2 d-flex justify-content-end gap-2">
                    <a href="{{ route('lessons.show', $lesson) }}" class="btn btn-sm my-btn-brand-primary">
                        Dettagli
                    </a>

                    @if (!$isCanceled)
                        <form method="POST" action="{{ route('lessons.cancel', $lesson) }}"
                            class="d-inline cancel-lesson-form">
                            @csrf
                            <button type="button" class="btn btn-sm btn-outline-danger open-cancel-modal"
                                data-lesson-id="{{ $lesson->id }}"
                                data-lesson-time="{{ $lesson->starts_at?->translatedFormat('H:i — d MMM') }}"
                                data-lesson-room="{{ $roomName }}"
                                data-lesson-operator="{{ optional($lesson->operator)->full_name ?? '—' }}">
                                Annulla
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('lessons.uncancel', $lesson) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success">Ripristina</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Lista iscritti (sempre completa) --}}
    <div class="card-body py-3">
        @if ($bookings->isEmpty())
            <div class="text-muted small">Nessun iscritto.</div>
        @else
            <ul class="list-unstyled mb-0">
                @foreach ($bookings as $b)
                    @php
                        $u = $b->user;
                        $fullName =
                            trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?:
                            $u->email ?? 'Utente #' . $u->id;

                        $usedPackage = (bool) $b->user_package_id;
                        $paid = (bool) ($b->paid ?? false);
                        $attended = (bool) ($b->attended ?? false);
                    @endphp

                    <li
                        class="d-flex align-items-center justify-content-between py-1 border-bottom border-light-subtle">
                        <span class="text-truncate me-3">{{ $fullName }}</span>

                        {{-- Mini recap stretta: Pacchetto / Pagato / Presenza --}}
                        <span class="d-inline-flex align-items-center gap-1">
                            @php $hasPackage = (bool) $b->user_package_id; @endphp

                            @if ($hasPackage)
                                {{-- Pacchetto usato: icona box con colore verde "pagato" --}}
                                <span class="badge rounded-pill text-bg-success" title="Pacchetto usato">
                                    <i class="fa-solid fa-box-open"></i>
                                </span>
                            @else
                                {{-- Nessun pacchetto: mostra SOLO lo stato di pagamento --}}
                                <span class="badge rounded-pill {{ $paid ? 'text-bg-success' : 'text-bg-danger' }}"
                                    title="Pagato">{{ $paid ? '€' : '€' }}</span>
                            @endif

                            {{-- Presenza --}}
                            <span class="badge rounded-pill {{ $attended ? 'text-bg-success' : 'text-bg-secondary' }}"
                                title="Presenza">{{ $attended ? '✓' : '—' }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@once
    {{-- Modal conferma annullamento --}}
    <div class="modal fade" id="cancelLessonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Conferma annullamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <p>Sei sicuro di voler annullare questa lezione?</p>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li><strong>Orario:</strong> <span id="clmTime"></span></li>
                        <li><strong>Sala:</strong> <span id="clmRoom"></span></li>
                        <li><strong>Operatore:</strong> <span id="clmOperator"></span></li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-danger" id="clmConfirmBtn">Conferma annullamento</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                let targetForm = null;
                let modal = null;
                const modalEl = document.getElementById('cancelLessonModal');

                // Registra SEMPRE i listener; la modale verrà creata lazy se/quando Bootstrap è disponibile
                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('.open-cancel-modal');
                    if (!btn) return;

                    e.preventDefault();
                    targetForm = btn.closest('form');

                    // popola i dati nella modale (o nel fallback)
                    const time = btn.dataset.lessonTime || '';
                    const room = btn.dataset.lessonRoom || '';
                    const op = btn.dataset.lessonOperator || '';

                    const timeEl = document.getElementById('clmTime');
                    const roomEl = document.getElementById('clmRoom');
                    const opEl = document.getElementById('clmOperator');
                    if (timeEl) timeEl.textContent = time;
                    if (roomEl) roomEl.textContent = room;
                    if (opEl) opEl.textContent = op;

                    // Crea la modale al volo se Bootstrap è disponibile
                    if (!modal && modalEl && window.bootstrap?.Modal) {
                        modal = new window.bootstrap.Modal(modalEl);
                    }

                    if (modal) {
                        modal.show();
                    } else {
                        // Fallback senza Bootstrap
                        if (confirm(`Annullare la lezione?\n\nOrario: ${time}\nSala: ${room}\nOperatore: ${op}`)) {
                            targetForm?.submit();
                        }
                    }
                });

                // conferma dal bottone nella modale (se c'è)
                document.getElementById('clmConfirmBtn')?.addEventListener('click', () => {
                    targetForm?.submit();
                });
            })
            ();
        </script>
    @endpush
@endonce
