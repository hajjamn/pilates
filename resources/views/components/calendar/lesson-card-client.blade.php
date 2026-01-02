@props(['lesson', 'canBookMore' => true])

@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $dt = $lesson->starts_at->locale('it');
    $prettyDate =
        Str::ucfirst($dt->isoFormat('ddd')) . ' ' . $dt->isoFormat('D') . ' ' . Str::ucfirst($dt->isoFormat('MMMM')); // es. "Lun 28 Agosto"

    $startTime = $dt->isoFormat('HH:mm');
    $endTime = $dt->copy()->addHour()->isoFormat('HH:mm');

    $authId = auth()->id();

    // Prenotazione ATTIVA dell'utente (deleted_at NULL)
// Prenotazione ATTIVA
$activeBooking = \App\Models\LessonUser::where('lesson_id', $lesson->id)
    ->where('user_id', $authId)
    ->whereNull('deleted_at')
    ->first();

// Qualsiasi prenotazione (attiva o cancellata)
$hasAnyBooking = \App\Models\LessonUser::where('lesson_id', $lesson->id)->where('user_id', $authId)->exists();

// Conteggio iscritti attivi (per capienza)
$activeCount = \App\Models\LessonUser::where('lesson_id', $lesson->id)->whereNull('deleted_at')->count();

$isFull = $activeCount >= $lesson->max_clients;
$seatsLeft = max(0, $lesson->max_clients - $activeCount);
$isCanceled = (bool) $lesson->canceled;
$isPast = $lesson->starts_at->isPast();

// ---- Regola disdetta: 6 ore lavorative (09:00–21:00) prima dell'inizio ----
    $tz = config('app.timezone', 'Europe/Rome');
    $nowRome = now($tz);
    $startRome = $lesson->starts_at->copy()->setTimezone($tz);

    $workingMinutes = 0;
    if ($nowRome->lt($startRome)) {
        $day = $nowRome->copy()->startOfDay();
        $last = $startRome->copy()->startOfDay();

        while ($day->lte($last)) {
            $dayStart = $day->copy()->setTime(9, 0);
            $dayEnd = $day->copy()->setTime(21, 0);

            $periodStart = $nowRome->greaterThan($dayStart) ? $nowRome->copy() : $dayStart;
            $periodEnd = $startRome->lessThan($dayEnd) ? $startRome->copy() : $dayEnd;

            if ($periodEnd->gt($periodStart)) {
                $workingMinutes += $periodEnd->diffInMinutes($periodStart);
            }
            $day->addDay();
        }
    }
    $canCancel = (bool) $activeBooking && $workingMinutes >= 6 * 60;
@endphp

<div class="card h-100 shadow-sm my-bg-brand-400">
    <div class="card-body d-flex flex-column gap-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-bold">
                    {{ Str::ucfirst($dt->isoFormat('ddd')) }}
                    {{ $dt->isoFormat('D') }}
                    {{ Str::ucfirst($dt->isoFormat('MMMM')) }}
                </div>
                <div class="text-muted">
                    {{ $startTime }}–{{ $endTime }}
                    @if ($lesson->room)
                        • {{ $lesson->room->name }}
                    @endif
                </div>
                @if ($lesson->operator)
                    <div class="small text-muted">
                        Operatrice: {{ $lesson->operator->full_name ?? $lesson->operator->email }}
                    </div>
                @endif
            </div>

            <div class="text-end">
                <div class="small">Posti: <strong>{{ $activeCount }}</strong> / {{ $lesson->max_clients }}</div>
                @if ($isCanceled)
                    <span class="badge text-bg-danger">Cancellata</span>
                @elseif ($isPast)
                    <span class="badge text-bg-secondary">Conclusa</span>
                @elseif ($isFull)
                    <span class="badge text-bg-warning">Completa</span>
                @else
                    <span class="badge text-bg-success">Attiva</span>
                @endif
                <div class="small {{ $isFull ? 'text-danger' : 'text-success' }}">
                    {{ $isFull ? 'Posti esauriti' : "Posti rimasti: $seatsLeft" }}
                </div>
            </div>
        </div>

        <div class="mt-2 d-flex flex-column gap-2">
            {{-- Pulsante principale (prenota / annulla / stato) --}}
            @if ($activeBooking)
                @if ($canCancel)
                    <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                        data-bs-target="#cancelModal-{{ $lesson->id }}">
                        Annulla prenotazione
                    </button>
                @else
                    <button class="btn btn-danger w-100" disabled
                        title="Cancellazione non consentita: servono almeno 6 ore lavorative (09:00–21:00) prima dell’inizio.">
                        Annulla prenotazione
                    </button>
                @endif
            @else
                @if ($isCanceled)
                    <button class="btn btn-secondary w-100" disabled>Lezione cancellata</button>
                @elseif ($isPast)
                    <button class="btn btn-secondary w-100" disabled>Lezione passata</button>
                @elseif ($isFull)
                    <button class="btn btn-secondary w-100" disabled>Posti esauriti</button>
                @else
                    @if (!$canBookMore)
                        <button type="button" class="btn btn-secondary w-100" disabled
                            title="Hai raggiunto il numero massimo di lezioni prenotate.">
                            Limite prenotazioni raggiunto
                        </button>
                        <div class="small text-danger mt-1">
                            Hai già {{ auth()->user()->future_active_lessons_count }}
                            lezioni prenotate. Limite massimo:
                            {{ auth()->user()->max_active_lesson_bookings }}.
                        </div>
                    @else
                        <button type="button" class="btn my-btn-accent-saffron text-white w-100" data-bs-toggle="modal"
                            data-bs-target="#bookModal-{{ $lesson->id }}">
                            Prenotati
                        </button>
                    @endif
                @endif

            @endif

            {{-- Nuovo: Dettaglio lezione --}}
            @if ($hasAnyBooking)
                <a href="{{ route('client.lessons.show', $lesson) }}" class="btn btn-outline-secondary w-100">
                    Dettagli
                </a>
            @endif
        </div>
    </div>
</div>

{{-- MODALE: Conferma iscrizione (solo se prenotabile) --}}
@if (!$activeBooking && !$isFull && !$isCanceled && !$isPast && $canBookMore)
    {{-- Nessuna scelta pacchetto lato UI: il server consumerà automaticamente i crediti se disponibili --}}
    <div class="modal fade" id="bookModal-{{ $lesson->id }}" tabindex="-1"
        aria-labelledby="bookLabel-{{ $lesson->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="bookLabel-{{ $lesson->id }}" class="modal-title">Conferma iscrizione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>

                <form method="POST" action="{{ route('lessons.book', $lesson) }}">
                    @csrf
                    <div class="modal-body">
                        <p>
                            Confermi l’iscrizione alla lezione delle <strong>{{ $startTime }}</strong> di
                            <strong>{{ $prettyDate }}</strong>?
                        </p>

                        <div class="small text-muted mt-2">
                            <p>Posti rimasti: {{ $seatsLeft }}</p>
                            <em>
                                NB: La cancellazione è possibile solo se restano almeno
                                <strong>6 ore</strong> (contate tra le <strong>09:00 e le 21:00</strong>) prima
                                dell’inizio.
                            </em>
                        </div>

                        <hr>
                        <p class="small text-muted mb-0">
                            Se hai crediti attivi in un pacchetto, all’iscrizione verrà scalato automaticamente 1
                            credito.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal" type="button">Annulla</button>
                        <button type="submit" class="btn btn-success">Conferma iscrizione</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif



{{-- MODALE: Conferma disdetta (solo se $canCancel === true) --}}
@if ($activeBooking && $canCancel)
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
                    <div class="small text-muted mt-2">
                        Le disdette sono consentite solo se ci sono almeno
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
