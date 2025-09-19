@extends('layouts.app')

@section('page-title', 'Crea nuova macchina')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">

        {{-- Header + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Crea nuova macchina</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('machines.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-chevron-left me-1"></i> Indietro
                </a>
            </div>
        </div>

        {{-- Errori --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="m-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Card form --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <strong>Dati macchina</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.machines.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label small">Nome <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                            class="form-control form-control-sm @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="room_id" class="form-label small">Sala <span class="text-danger">*</span></label>
                        <select id="room_id" name="room_id"
                            class="form-select form-select-sm @error('room_id') is-invalid @enderror" required>
                            <option value="">— Seleziona sala —</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', $selectedRoomId ?? null) == $room->id)>
                                    {{ $room->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if (!$rooms->count())
                            <div class="form-text">Nessuna sala disponibile: crea prima una sala.</div>
                        @endif
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label small">Descrizione</label>
                        <textarea id="description" name="description" rows="4"
                            class="form-control form-control-sm @error('description') is-invalid @enderror" placeholder="Opzionale...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('machines.index') }}" class="btn btn-sm btn-outline-secondary">Annulla</a>
                        <button type="submit" class="btn btn-sm my-btn-brand-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
