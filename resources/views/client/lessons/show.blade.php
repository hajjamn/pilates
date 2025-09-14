@extends('layouts.app')
@section('page-title', 'Dettaglio lezione')

@section('content')
    <div class="container mb-4" style="max-width: 760px;">
        <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">
            ← Indietro
        </a>

        <div class="card shadow-sm">

            @php
                $now = now();
                $isFuture = $lesson->starts_at->gt($now);
            @endphp

            @if ($isLessonCanceled)
                <div class="card-header bg-danger text-white">
                    ⚠️ Questa lezione è stata annullata dall’organizzazione
                </div>
            @endif

            <div class="card-body">
                <div class="text-muted small mb-1">Lezione</div>
                <h1 class="h5 mb-3">
                    {{ $lesson->starts_at->translatedFormat('l d F') }} · {{ $lesson->starts_at->format('H:i') }}
                </h1>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <div><strong>Istruttore:</strong> {{ optional($lesson->operator)->full_name ?? '—' }}</div>
                        <div><strong>Email istruttore:</strong>
                            @if (optional($lesson->operator)->email)
                                <a href="mailto:{{ $lesson->operator->email }}">{{ $lesson->operator->email }}</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div><strong>Sala:</strong> {{ optional($lesson->room)->name ?? '—' }}</div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-1 text-muted small">Stato pagamento</div>
                        @php
                            $paidLabel = is_null($paid) ? '—' : ($paid ? 'Pagata' : 'Da pagare');
                            $paidClass = is_null($paid) ? 'secondary' : ($paid ? 'success' : 'warning');
                        @endphp
                        <span class="badge bg-{{ $paidClass }}">{{ $paidLabel }}</span>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-1 text-muted small">Presenza</div>
                        @php
                            $attLabel = is_null($attended) ? '—' : ($attended ? 'Presente' : 'Assente');
                            $attClass = is_null($attended) ? 'secondary' : ($attended ? 'success' : 'danger');
                        @endphp
                        <span class="badge bg-{{ $attClass }}">{{ $attLabel }}</span>
                    </div>
                </div>

                {{-- NUOVO: metodo pagamento (pacchetto vs no) --}}
                <div class="mt-2 text-muted small">
                    Metodo pagamento:
                    @if ($paidViaPackage)
                        <strong>Pacchetto{{ $packageName ? ' — ' . $packageName : '' }}</strong>
                    @else
                        <strong>Senza pacchetto</strong>
                    @endif
                </div>


                <hr class="my-3">

                @php
                    $op = optional($lesson->operator);
                    $opPhone = $op?->phone ?? null; // aggiorna se il campo ha un nome diverso
                    $digits = $opPhone ? preg_replace('/\D+/', '', $opPhone) : null;
                    // fallback prefisso IT se il numero inizia con 0 ed è privo di +39
                    if ($digits && str_starts_with($digits, '0')) {
                        $digits = '39' . $digits;
                    }
                    $waText = rawurlencode(
                        'Ciao! Ti scrivo riguardo la lezione del ' . $lesson->starts_at->format('d/m/Y H:i') . '.',
                    );
                @endphp

                <div class="d-flex flex-wrap gap-2">
                    @if ($op?->email)
                        <a class="btn btn-outline-primary btn-sm" href="mailto:{{ $op->email }}">
                            <i class="fa-regular fa-envelope me-1"></i> Invia email
                        </a>
                    @else
                        <button class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fa-regular fa-envelope me-1"></i> Email non disponibile
                        </button>
                    @endif

                    @if ($digits)
                        <a class="btn btn-outline-success btn-sm"
                            href="https://wa.me/{{ $digits }}?text={{ $waText }}" target="_blank"
                            rel="noopener">
                            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                        </a>
                    @else
                        <button class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp non disponibile
                        </button>
                    @endif

                    <a class="btn btn-outline-secondary btn-sm"
                        href="{{ route('calendar.lessons.index', ['date' => $lesson->starts_at->toDateString()]) }}">
                        Calendario
                    </a>

                    @if (!$isLessonCanceled && $isFuture)
                        @if ($booking)
                            {{-- $booking è un LessonUser: la rotta expects {booking} --}}
                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                                onsubmit="return confirm('Vuoi annullare la tua prenotazione?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit">
                                    Annulla prenotazione
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('lessons.book', $lesson) }}">
                                @csrf
                                <button class="btn btn-outline-primary btn-sm" type="submit">
                                    Prenota
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
