@extends('layouts.app')

@section('page-title', 'Modifica pacchetto')

@section('content')
    <div class="container mt-4" style="max-width:800px;">
        {{-- Header + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Modifica pacchetto</h1>
            <a href="{{ route('packages.show', $package) }}" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-chevron-left me-1"></i> Indietro
            </a>
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
                <strong>Dati pacchetto</strong>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.packages.update', $package) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-12">
                        <label for="name" class="form-label small">Nome <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $package->name) }}"
                            class="form-control form-control-sm @error('name') is-invalid @enderror" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="total_lessons" class="form-label small">Numero lezioni <span
                                class="text-danger">*</span></label>
                        <input type="number" id="total_lessons" name="total_lessons" min="1" step="1"
                            value="{{ old('total_lessons', $package->total_lessons) }}"
                            class="form-control form-control-sm @error('total_lessons') is-invalid @enderror" required>
                        @error('total_lessons')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="price" class="form-label small">Prezzo (€) <span class="text-danger">*</span></label>
                        <input type="number" id="price" name="price" min="0" step="0.01"
                            value="{{ old('price', $package->price) }}"
                            class="form-control form-control-sm @error('price') is-invalid @enderror" required>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('packages.show', $package) }}"
                            class="btn btn-sm btn-outline-secondary">Annulla</a>
                        <button type="submit" class="btn btn-sm my-btn-brand-primary">Aggiorna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
