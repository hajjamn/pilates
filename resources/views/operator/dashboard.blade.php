@extends('layouts.app')
@section('page-title', 'Dashboard Operatore')

@section('content')
    <div class="container my-4 operator-dashboard">

        {{-- Titolo --}}
        <h2 class="h4 text-center mb-4">Lezioni di oggi</h2>

        {{-- Lezione corrente --}}
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
                        <div class="col-sm-6"><strong>Iscritti:</strong> {{ $currentLesson->clients->count() }}</div>
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

        {{-- Lezioni future --}}
        @if ($futureLessons->count())
            <h3 class="h5 mt-4 mb-3">Prossime lezioni</h3>
            <div class="list-group mb-4">
                @foreach ($futureLessons as $lesson)
                    <a href="{{ route('lessons.show', $lesson) }}"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            {{ $lesson->starts_at->format('H:i') }} — {{ optional($lesson->room)->name ?? '—' }}
                        </div>
                        <span class="badge bg-secondary">{{ $lesson->clients->count() }} iscritti</span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-muted text-center mb-4">Nessuna lezione futura</p>
        @endif

        {{-- Lezioni concluse --}}
        @if ($pastLessons->count())
            <h3 class="h5 mt-4 mb-3">Lezioni concluse</h3>
            <div class="accordion" id="pastLessonsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="pastLessonsHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#pastLessonsCollapse">
                            Vedi lezioni concluse
                        </button>
                    </h2>
                    <div id="pastLessonsCollapse" class="accordion-collapse collapse">
                        <div class="accordion-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach ($pastLessons as $lesson)
                                    <a href="{{ route('lessons.show', $lesson) }}"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>{{ $lesson->starts_at->format('H:i') }} —
                                            {{ optional($lesson->room)->name ?? '—' }}</span>
                                        <span class="badge bg-light text-dark">{{ $lesson->clients->count() }}
                                            iscritti</span>
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

    </div>
@endsection
