@props(['lesson'])

@php

    $dt = $lesson->starts_at->locale('it');
    $prettyDate =
        \Illuminate\Support\Str::ucfirst($dt->isoFormat('ddd')) .
        ' ' .
        $dt->isoFormat('D') .
        ' ' .
        \Illuminate\Support\Str::ucfirst($dt->isoFormat('MMMM')); // es. "Lun 28 Agosto"
    $startTime = $dt->isoFormat('HH:mm');
    $endTime = $dt->copy()->addHour()->isoFormat('HH:mm');

    $authId = auth()->id();

    // Prenotazione ATTIVA dell'utente su questa lezione (soft-delete = NULL)
$activeBooking = \App\Models\LessonUser::where('lesson_id', $lesson->id)
    ->where('user_id', $authId)
    ->whereNull('deleted_at')
    ->first();

// Quanti iscritti attivi ci sono (per mostrare posti rimasti / bottone disabilitato)
$activeCount = \App\Models\LessonUser::where('lesson_id', $lesson->id)->whereNull('deleted_at')->count();

    $isFull = $activeCount >= $lesson->max_clients;
    $seatsLeft = max(0, $lesson->max_clients - $activeCount);
@endphp


<div class="card h-100 shadow-sm">
    <div class="card-body d-flex flex-column gap-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold">
                    {{ \Illuminate\Support\Str::ucfirst($dt->isoFormat('ddd')) }}
                    {{ $dt->isoFormat('D') }}
                    {{ \Illuminate\Support\Str::ucfirst($dt->isoFormat('MMMM')) }}
                </div>
                <div class="text-muted">
                    {{ $lesson->starts_at->format('H:i') }}–{{ $lesson->starts_at->copy()->addHour()->format('H:i') }}
                    @if ($lesson->room)
                        • Sala {{ $lesson->room->name }}
                    @endif
                </div>
                @if ($lesson->operator)
                    <div class="small text-muted">Operatrice:
                        {{ $lesson->operator->full_name ?? $lesson->operator->email }}</div>
                @endif
            </div>
            <div class="text-end">
                <div class="small">Posti: <strong>{{ $activeCount }}</strong> / {{ $lesson->max_clients }}</div>
                <div class="small {{ $isFull ? 'text-danger' : 'text-success' }}">
                    {{ $isFull ? 'Completa' : "Posti rimasti: $seatsLeft" }}
                </div>
            </div>
        </div>

        <div class="mt-2">
            @if ($activeBooking)
                {{-- Bottone: disdetta --}}
                <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                    data-bs-target="#cancelModal-{{ $lesson->id }}">
                    Annulla prenotazione
                </button>
            @elseif ($isFull)
                {{-- Classe piena --}}
                <button class="btn btn-secondary w-100" disabled>Posti esauriti</button>
            @else
                {{-- Bottone: prenotati --}}
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal"
                    data-bs-target="#bookModal-{{ $lesson->id }}">
                    Prenotati
                </button>
            @endif
        </div>
    </div>
</div>

{{-- MODALE: Conferma iscrizione --}}
@if (!$activeBooking && !$isFull)
    <div class="modal fade" id="bookModal-{{ $lesson->id }}" tabindex="-1"
        aria-labelledby="bookLabel-{{ $lesson->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="bookLabel-{{ $lesson->id }}" class="modal-title">Conferma iscrizione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    Confermi l’iscrizione alla lezione delle <strong>{{ $startTime }}</strong> di
                    <strong>{{ $prettyDate }}</strong>?
                    ?
                    <div class="small text-muted mt-2">
                        Posti rimasti: {{ $seatsLeft }} • La cancellazione è possibile solo se ci sono almeno
                        <strong>6 ore</strong> (contate tra <strong>09:00–21:00</strong>) prima dell’inizio.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
                    <form method="POST" action="{{ route('lessons.book', $lesson) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Conferma iscrizione</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- MODALE: Conferma disdetta --}}
@if ($activeBooking)
    <div class="modal fade" id="cancelModal-{{ $lesson->id }}" tabindex="-1"
        aria-labelledby="cancelLabel-{{ $lesson->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="cancelLabel-{{ $lesson->id }}" class="modal-title">Conferma disdetta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    Vuoi annullare la prenotazione per le <strong>{{ $startTime }}</strong> di
                    <strong>{{ $prettyDate }}</strong>?
                    ?
                    <div class="small text-muted mt-2">
                        Nota: le disdette dei clienti sono consentite solo se ci sono almeno
                        <strong>6 ore</strong> lavorative (09:00–21:00) prima dell’inizio.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Mantieni</button>
                    <form method="POST" action="{{ route('bookings.cancel', $activeBooking) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Conferma disdetta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
