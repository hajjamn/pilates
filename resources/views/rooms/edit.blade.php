@extends('layouts.app')

@section('page-title', 'Modifica sala')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">

        {{-- Header + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Modifica sala: {{ $room->name }}</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('rooms.index') }}" class="btn btn-sm btn-secondary">
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
                <strong>Dati sala</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.rooms.update', $room) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label small">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                            id="name" name="name" value="{{ old('name', $room->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="max_clients" class="form-label small">Capienza massima <span
                                class="text-danger">*</span></label>
                        <input type="number" id="max_clients" name="max_clients"
                            class="form-control form-control-sm @error('max_clients') is-invalid @enderror" min="1"
                            max="100" step="1" inputmode="numeric" required
                            value="{{ old('max_clients', $room->max_clients) }}">
                        <div class="form-text">Valori consentiti: 1–100.</div>
                        @error('max_clients')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label small">Descrizione</label>
                        <textarea class="form-control form-control-sm @error('description') is-invalid @enderror" id="description"
                            name="description" rows="4" placeholder="Opzionale...">{{ old('description', $room->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('rooms.index') }}" class="btn btn-sm btn-outline-secondary">Annulla</a>
                        <button type="submit" class="btn btn-sm my-btn-brand-primary">Aggiorna</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
