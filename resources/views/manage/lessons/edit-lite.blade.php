@extends('layouts.app')

@section('page-title', 'Modifica lezione (operatore)')

@section('content')
    <div class="container mt-4" style="max-width:700px;">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Modifica lezione</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('lessons.show', $lesson) }}" class="btn btn-outline-secondary btn-sm">Dettagli</a>
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Indietro</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Correggi gli errori:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('lessons.updateLite', $lesson) }}" class="card">
            @csrf
            @method('PATCH')

            <div class="card-body">
                <div class="row g-3">

                    {{-- Sala (solo lettura) --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Sala</label>
                        <input type="text" class="form-control" value="{{ $lesson->room?->name }}" disabled>
                    </div>

                    {{-- Operatore (solo lettura) --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Operatore</label>
                        <input type="text" class="form-control"
                            value="{{ $lesson->operator?->full_name ?? auth()->user()->name }}" disabled>
                    </div>

                    {{-- Data/Ora inizio --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Data/Ora inizio</label>
                        <input type="datetime-local" name="starts_at" class="form-control"
                            value="{{ $lesson->starts_at ? $lesson->starts_at->format('Y-m-d\TH:i') : '' }}" required>
                    </div>

                    {{-- Capienza --}}
                    <div class="col-12 col-md-3">
                        <label class="form-label small">Capienza max</label>
                        <input type="number" name="max_clients" class="form-control" min="1" max="200"
                            value="{{ old('max_clients', $lesson->max_clients) }}" required>
                    </div>

                    {{-- Canceled (toggle) --}}
                    <div class="col-12 col-md-3">
                        <label class="form-label small d-block">Stato</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="canceled" id="canceled" value="1"
                                @checked($lesson->canceled)>
                            <label class="form-check-label" for="canceled">Annullata</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('lessons.show', $lesson) }}" class="btn btn-outline-secondary">Annulla</a>
                <button class="btn my-btn-brand-primary">Salva modifiche</button>
            </div>
        </form>
    </div>
@endsection
