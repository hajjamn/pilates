{{-- resources/views/manage/lessons/edit.blade.php --}}
@extends('layouts.app')

@section('page-title', 'Modifica lezione (admin)')

@section('content')
    <div class="container" style="max-width:900px;">

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

        <form method="POST" action="{{ route('lessons.update', $lesson) }}" class="card">
            @csrf
            @method('PATCH')

            <div class="card-body">
                <div class="row g-3">
                    {{-- Sala --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Sala</label>
                        <select name="room_id" class="form-select" required>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected((int) $lesson->room_id === (int) $room->id)>
                                    {{ $room->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Operatore --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Operatore</label>
                        <select name="operator_id" class="form-select" required>
                            @foreach ($operators as $op)
                                @php
                                    $label = trim(($op->first_name ?? '') . ' ' . ($op->last_name ?? '')) ?: $op->email;
                                @endphp
                                <option value="{{ $op->id }}" @selected((int) $lesson->operator_id === (int) $op->id)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Data/Ora inizio --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Data/Ora inizio</label>
                        <input type="datetime-local" name="starts_at" class="form-control"
                            value="{{ $lesson->starts_at ? $lesson->starts_at->format('Y-m-d\TH:i') : '' }}" required>
                        <div class="form-text">Assicurati che non ci siano conflitti sala/operatore.</div>
                    </div>

                    {{-- Capienza --}}
                    <div class="col-12 col-md-3">
                        <label class="form-label small">Capienza max</label>
                        <input type="number" name="max_clients" class="form-control" min="1" max="200"
                            value="{{ old('max_clients', $lesson->max_clients) }}" required>
                    </div>

                    {{-- Flag --}}
                    <div class="col-12 col-md-3">
                        <label class="form-label small d-block">Stato</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="canceled" id="canceled" value="1"
                                @checked($lesson->canceled)>
                            <label class="form-check-label" for="canceled">Annullata</label>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="manual_override" id="manual_override"
                                value="1" @checked($lesson->manual_override)>
                            <label class="form-check-label" for="manual_override">Manual override</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('lessons.show', $lesson) }}" class="btn btn-outline-secondary">Annulla</a>
                <button class="btn btn-primary">Salva modifiche</button>
            </div>
        </form>
    </div>
@endsection
