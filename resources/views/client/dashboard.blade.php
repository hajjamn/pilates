@extends('layouts.app')
@section('page-title', 'Homepage')

@section('content')
    <div class="container">

        <h2 class="h4">Calendario Prenotazioni</h2>
        <div class="d-grid gap-2 my-3" style="max-width:520px;">
            <a class="btn btn-primary disabled" href="#">Questa settimana ▸</a>
            <a class="btn btn-outline-success disabled" href="#">Ricerca per sala ▸</a>
            <a class="btn btn-outline-success disabled" href="#">Ricerca per istruttore ▸</a>
        </div>

        <h2 class="h4 mt-4">Lezioni Prenotate</h2>

        @if ($nextLesson)
            <div class="card shadow-sm" style="max-width: 720px;">
                <div class="card-body">
                    <div class="text-muted small mb-1">Prossima Lezione</div>

                    <h3 class="h5 mb-2">
                        {{ $nextLesson->starts_at->translatedFormat('l d F') }}
                        · {{ $nextLesson->starts_at->format('H:i') }}
                    </h3>

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Istruttore:</strong>
                            {{ optional($nextLesson->operator)->full_name ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Sala:</strong>
                            {{ optional($nextLesson->room)->name ?? '—' }}
                        </div>
                    </div>

                    {{-- CTA segnaposto --}}
                    <div class="mt-3 d-flex gap-2">
                        <a href="#" class="btn btn-sm btn-outline-primary disabled">Dettagli lezione ▸</a>
                        <a href="#" class="btn btn-sm btn-outline-secondary disabled">Calendario ▸</a>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info" style="max-width: 720px;">
                Nessuna prossima lezione prenotata.
            </div>
        @endif

        <div class="d-grid gap-2 my-3" style="max-width:520px;">
            <a class="btn btn-outline-secondary disabled" href="#">Prossime lezioni ▸</a>
            <a class="btn btn-outline-secondary disabled" href="#">Storico lezioni ▸</a>
        </div>
    </div>
@endsection
