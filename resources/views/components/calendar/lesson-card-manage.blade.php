@props([
    'lesson',
    'mode' => 'operator', // 'operator' | 'admin'
])

@php
    $isCanceled = (bool) $lesson->canceled;
    $isPast = $lesson->starts_at?->isPast();
    $bookings = $lesson->lessonUsers ?? collect();
    $clientsCount = $bookings->count();
    $max = $lesson->max_clients ?? null;

    $roomName = $lesson->room?->name ?? '—';

    $statusLabel = $isCanceled ? 'Annullata' : ($isPast ? 'Conclusa' : 'Attiva');
    $statusClass = $isCanceled
        ? 'bg-danger-subtle text-danger'
        : ($isPast
            ? 'bg-secondary-subtle text-secondary'
            : 'bg-success-subtle text-success');

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
                            {{-- Pacchetto: solo indicatore "usato" (gli operatori non vedono i nomi) --}}
                            @if ($usedPackage)
                                <span class="badge rounded-pill text-bg-light" title="Pacchetto usato">Pkg</span>
                            @else
                                <span class="badge rounded-pill text-bg-light text-muted"
                                    title="Nessun pacchetto">—</span>
                            @endif

                            {{-- Pagato --}}
                            <span class="badge rounded-pill {{ $paid ? 'text-bg-success' : 'text-bg-danger' }}"
                                title="Pagato">{{ $paid ? '€' : '€' }}</span>

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
