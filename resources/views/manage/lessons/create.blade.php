{{-- resources/views/manage/lessons/create.blade.php --}}
@extends('layouts.app')

@section('page-title', 'Crea nuova lezione')

@section('content')
    <div class="container" style="max-width:900px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Nuova lezione</h1>
            <a href="{{ route('calendar.lessons.index') }}" class="btn btn-outline-secondary btn-sm">Torna al calendario</a>
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

        <form method="POST" action="{{ route('lessons.store') }}" class="card">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    {{-- Sala --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Sala</label>
                        <select name="room_id" class="form-select" required>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected((int) old('room_id', $defaults['room_id']) === (int) $room->id)>
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
                                <option value="{{ $op->id }}" @selected((int) old('operator_id', $defaults['operator_id']) === (int) $op->id)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Data/Ora inizio --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Data/Ora inizio</label>
                        <input type="datetime-local" name="starts_at" class="form-control"
                            value="{{ old('starts_at', $defaults['starts_at']) }}" required>
                    </div>

                    {{-- Capienza --}}
                    <div class="col-12 col-md-3">
                        <label class="form-label small">Capienza max</label>
                        <input type="number" name="max_clients" class="form-control" min="1" max="200"
                            value="{{ old('max_clients', $defaults['max_clients']) }}" required>
                    </div>

                    {{-- Manual override (di default true per create manuale? lasciamo opzionale) --}}
                    <div class="col-12 col-md-3">
                        <label class="form-label small d-block">Opzioni</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="manual_override" id="manual_override"
                                value="1" @checked(old('manual_override', true))>
                            <label class="form-check-label" for="manual_override">Manual override</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('calendar.lessons.index') }}" class="btn btn-outline-secondary">Annulla</a>
                <button class="btn btn-success">Crea lezione</button>
            </div>
        </form>
    </div>
@endsection
