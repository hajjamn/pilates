@props(['lesson'])

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
$activeBooking = \App\Models\LessonUser::where('lesson_id', $lesson->id)
    ->where('user_id', $authId)
    ->whereNull('deleted_at')
    ->first();

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

<div class="card h-100 shadow-sm">
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
                        • Sala {{ $lesson->room->name }}
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

        <div class="mt-2">
            @if ($activeBooking)
                {{-- Bottone: disdetta (abilitato solo se $canCancel) --}}
                @if ($canCancel)
                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                        data-bs-target="#cancelModal-{{ $lesson->id }}">
                        Annulla prenotazione
                    </button>
                @else
                    <button class="btn btn-outline-danger w-100" disabled
                        title="Cancellazione non consentita: servono almeno 6 ore lavorative (09:00–21:00) prima dell’inizio.">
                        Annulla prenotazione
                    </button>
                @endif
            @else
                {{-- Prenotazione (consentita solo se non piena, non cancellata, non passata) --}}
                @if ($isCanceled)
                    <button class="btn btn-secondary w-100" disabled>Lezione cancellata</button>
                @elseif ($isPast)
                    <button class="btn btn-secondary w-100" disabled>Lezione passata</button>
                @elseif ($isFull)
                    <button class="btn btn-secondary w-100" disabled>Posti esauriti</button>
                @else
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#bookModal-{{ $lesson->id }}">
                        Prenotati
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>

{{-- MODALE: Conferma iscrizione (solo se prenotabile) --}}
@if (!$activeBooking && !$isFull && !$isCanceled && !$isPast)
    @php
        // Pacchetti attivi dell'utente (MVP: query qui; in futuro puoi passarli dal controller)
$activePackages = auth()->user()
    ? \App\Models\UserPackage::where('user_id', auth()->id())
        ->where('lessons_remaining', '>', 0)
        ->orderBy('purchased_at')
        ->with('package') // per mostrare nome pacchetto
                ->get()
            : collect();

        $hasPackages = $activePackages->isNotEmpty();
        $singlePackage = $hasPackages && $activePackages->count() === 1 ? $activePackages->first() : null;
    @endphp

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
                            Posti rimasti: {{ $seatsLeft }} • La cancellazione è possibile solo se ci sono almeno
                            <strong>6 ore</strong> (contate tra <strong>09:00–21:00</strong>) prima dell’inizio.
                        </div>

                        @if ($hasPackages)
                            <hr>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="usePackage-{{ $lesson->id }}"
                                    name="use_package" value="1">
                                <label class="form-check-label" for="usePackage-{{ $lesson->id }}">
                                    Usa una lezione da pacchetto
                                </label>
                            </div>

                            @if ($singlePackage)
                                {{-- Un solo pacchetto: lo selezioniamo in automatico quando la checkbox è spuntata --}}
                                <input type="hidden" name="user_package_id" value="{{ $singlePackage->id }}"
                                    data-bind-to="usePackageSelect-{{ $lesson->id }}">
                                <div id="pkgInfo-{{ $lesson->id }}" class="small text-muted ms-4 mt-1"
                                    style="display:none;">
                                    Verrà usato: <strong>{{ $singlePackage->package?->name ?? 'Pacchetto' }}</strong>
                                    (crediti rimasti: {{ $singlePackage->lessons_remaining }})
                                </div>
                            @else
                                {{-- Più pacchetti: mostra select quando la checkbox è spuntata --}}
                                <div id="pkgSelectWrap-{{ $lesson->id }}" class="mt-2" style="display:none;">
                                    <label for="usePackageSelect-{{ $lesson->id }}" class="form-label small mb-1">
                                        Seleziona pacchetto
                                    </label>
                                    <select class="form-select" name="user_package_id"
                                        id="usePackageSelect-{{ $lesson->id }}">
                                        @foreach ($activePackages as $up)
                                            <option value="{{ $up->id }}">
                                                {{ $up->package?->name ?? 'Pacchetto #' . $up->id }}
                                                — crediti: {{ $up->lessons_remaining }}
                                                — acquistato il {{ $up->purchased_at?->format('d/m/Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal" type="button">Annulla</button>
                        <button type="submit" class="btn btn-primary">Conferma iscrizione</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($hasPackages)
        <script>
            (function() {
                const cb = document.getElementById('usePackage-{{ $lesson->id }}');
                const info = document.getElementById('pkgInfo-{{ $lesson->id }}');
                const wrap = document.getElementById('pkgSelectWrap-{{ $lesson->id }}');
                if (!cb) return;
                const toggle = () => {
                    const on = cb.checked;
                    if (info) info.style.display = on ? '' : 'none';
                    if (wrap) wrap.style.display = on ? '' : 'none';
                };
                cb.addEventListener('change', toggle);
                // default off
                toggle();
            })();
        </script>
    @endif
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
