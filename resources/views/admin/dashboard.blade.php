@extends('layouts.app')
@section('page-title', 'Dashboard Admin')

@section('content')
    <div class="container my-4 admin-dashboard">

        {{-- ====== Sezione: Le mie lezioni (Admin come Operatore) ====== --}}
        <h2 class="h4 text-center mb-4">Le mie lezioni di oggi</h2>

        {{-- Lezione corrente (mia) --}}
        @if ($currentLesson)
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="text-muted small mb-1">Lezione in corso</div>
                    <h3 class="h5 mb-2">
                        {{ ucfirst($currentLesson->starts_at->translatedFormat('l d F')) }}
                        - {{ $currentLesson->starts_at->format('H:i') }}
                    </h3>

                    <div class="row">
                        <div class="col-sm-6"><strong>Sala:</strong> {{ optional($currentLesson->room)->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Iscritti:</strong> {{ $currentLesson->clients_count }}</div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('lessons.show', $currentLesson) }}" class="btn btn-sm my-btn-brand-primary">
                            Dettagli <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @else
            <p class="text-muted text-center mb-4">Nessuna lezione in corso</p>
        @endif

        {{-- Lezioni future (mie) --}}
        @if ($futureLessons->count())
            <h3 class="h5 mt-4 mb-3">Prossime lezioni</h3>
            <div class="list-group mb-4">
                @foreach ($futureLessons as $lesson)
                    <a href="{{ route('lessons.show', $lesson) }}"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            {{ $lesson->starts_at->format('H:i') }} — {{ optional($lesson->room)->name ?? '—' }}
                        </div>
                        @if ($lesson->canceled)
                            <span class="badge bg-danger">Annullata</span>
                        @else
                            <span class="badge bg-secondary">{{ $lesson->clients_count }} iscritti</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-muted text-center mb-4">Nessuna lezione futura</p>
        @endif

        {{-- Lezioni concluse (mie) --}}
        @if ($pastLessons->count())
            <h3 class="h5 mt-4 mb-3">Lezioni concluse</h3>
            <div class="accordion" id="pastLessonsAccordionMine">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="pastLessonsHeadingMine">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#pastLessonsCollapseMine">
                            Vedi lezioni concluse
                        </button>
                    </h2>
                    <div id="pastLessonsCollapseMine" class="accordion-collapse collapse">
                        <div class="accordion-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach ($pastLessons as $lesson)
                                    <a href="{{ route('lessons.show', $lesson) }}"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>{{ $lesson->starts_at->format('H:i') }} —
                                            {{ optional($lesson->room)->name ?? '—' }}</span>
                                        @if ($lesson->canceled)
                                            <span class="badge bg-danger">Annullata</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $lesson->clients_count }} iscritti</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-muted text-center">Nessuna lezione conclusa</p>
        @endif


        {{-- ====== Sezione: Lezioni di oggi — altri operatori ====== --}}
        <hr class="my-5">

        <h2 class="h4 text-center mb-4">Lezioni di oggi — altri operatori</h2>

        {{-- Lezioni in corso (altri) --}}
        <h3 class="h6 mt-3 mb-2 text-muted">Lezioni in corso</h3>
        @if ($currentLessonsOthers->count())
            <div class="list-group mb-4">
                @foreach ($currentLessonsOthers as $lesson)
                    <a href="{{ route('lessons.show', $lesson) }}"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            {{ $lesson->starts_at->format('H:i') }} — {{ optional($lesson->room)->name ?? '—' }}
                            — {{ optional($lesson->operator)->first_name ?? '—' }}
                        </div>
                        <span class="badge bg-secondary">{{ $lesson->clients_count }} iscritti</span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-muted">Nessuna lezione in corso per altri operatori</p>
        @endif


        {{-- Lezioni future (altri) --}}
        <h3 class="h6 mt-4 mb-2 text-muted">Prossime lezioni</h3>
        @if ($futureLessonsOthers->count())
            <div class="list-group mb-4">
                @foreach ($futureLessonsOthers as $lesson)
                    <a href="{{ route('lessons.show', $lesson) }}"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            {{ $lesson->starts_at->format('H:i') }} — {{ optional($lesson->room)->name ?? '—' }}
                            — {{ optional($lesson->operator)->first_name ?? '—' }}
                        </div>
                        @if ($lesson->canceled)
                            <span class="badge bg-danger">Annullata</span>
                        @else
                            <span class="badge bg-secondary">{{ $lesson->clients_count }} iscritti</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-muted">Nessuna lezione futura per altri operatori</p>
        @endif


        {{-- Lezioni concluse (altri) --}}
        <h3 class="h6 mt-4 mb-2 text-muted">Lezioni concluse</h3>
        @if ($pastLessonsOthers->count())
            <div class="accordion" id="pastLessonsAccordionOthers">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="pastLessonsHeadingOthers">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#pastLessonsCollapseOthers">
                            Vedi lezioni concluse (altri operatori)
                        </button>
                    </h2>
                    <div id="pastLessonsCollapseOthers" class="accordion-collapse collapse">
                        <div class="accordion-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach ($pastLessonsOthers as $lesson)
                                    <a href="{{ route('lessons.show', $lesson) }}"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            {{ $lesson->starts_at->format('H:i') }} —
                                            {{ optional($lesson->room)->name ?? '—' }}
                                            — {{ optional($lesson->operator)->first_name ?? '—' }}
                                        </div>
                                        @if ($lesson->canceled)
                                            <span class="badge bg-danger">Annullata</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $lesson->clients_count }} iscritti</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-muted">Nessuna lezione conclusa per altri operatori</p>
        @endif


    </div>
@endsection
