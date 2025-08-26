@props(['lesson'])

@php
    $starts = $lesson->starts_at;
    $isPast = $starts?->isPast();
    $isCancel = (bool) $lesson->canceled;
    $capacity = (int) $lesson->max_clients;
    $booked = (int) ($lesson->clients_count ?? 0);
    $isFull = $capacity > 0 ? $booked >= $capacity : false;
    $already = (int) ($lesson->is_booked ?? 0) > 0; // solo per clienti

    $opName = $lesson->operator?->full_name ?? ($lesson->operator?->name ?? ($lesson->operator?->email ?? '—'));

    $roomName = $lesson->room?->name ?? '—';
@endphp

<div class="card shadow-sm mb-3" style="border-radius:14px; overflow:hidden;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="fw-bold" style="font-size:1.05rem;">
                    {{ $starts?->format('H:i') }}
                    <span class="text-muted">•</span>
                    {{ $roomName }}
                </div>
                <div class="text-muted">
                    Operatore: <span class="fw-semibold">{{ $opName }}</span>
                </div>
            </div>

            {{-- Badges stato --}}
            <div class="d-flex gap-2">
                @if ($isCancel)
                    <span class="badge text-bg-danger">Cancellata</span>
                @elseif($isPast)
                    <span class="badge text-bg-secondary">Conclusa</span>
                @elseif($isFull)
                    <span class="badge text-bg-warning">Piena</span>
                @else
                    <span class="badge text-bg-success">Aperta</span>
                @endif
            </div>
        </div>

        <div class="mt-2 text-muted">
            Posti: <strong>{{ $booked }}</strong> / {{ $capacity }}
        </div>

        <div class="mt-3 d-flex gap-2">
            @if ($isCancel || $isPast)
                <button class="btn btn-outline-secondary btn-sm" disabled>Non disponibile</button>
            @elseif($already)
                <button class="btn btn-outline-primary btn-sm" disabled>Già prenotata</button>
                <button class="btn btn-outline-danger btn-sm" disabled>Disdici</button> {{-- placeholder, attiveremo più avanti --}}
            @elseif($isFull)
                <button class="btn btn-outline-secondary btn-sm" disabled>Lista d’attesa</button> {{-- placeholder --}}
            @else
                <a class="btn btn-primary btn-sm" href="#" role="button">Prenota</a> {{-- placeholder rotta prenotazione --}}
            @endif
        </div>
    </div>
</div>
