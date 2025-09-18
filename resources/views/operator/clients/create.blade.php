@extends('layouts.app')

@section('page-title', 'Crea cliente')

@section('content')
    <div class="container my-4">

        {{-- Header + back --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Crea cliente</h1>
            <a href="{{ route('operator.dashboard') }}" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-chevron-left"></i> Indietro
            </a>
        </div>

        {{-- Errori validazione (summary) --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Correggi i campi indicati:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('operator.clients.store') }}" novalidate>
            @csrf

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input name="first_name" type="text"
                                class="form-control @error('first_name') is-invalid @enderror"
                                value="{{ old('first_name') }}" required autofocus>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Cognome <span class="text-danger">*</span></label>
                            <input name="last_name" type="text"
                                class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}"
                                required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Servirà per accedere e ricevere comunicazioni.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Telefono (opz.)</label>
                            <input name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}" placeholder="+39 333 1234567">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Verrà normalizzato in formato internazionale.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Data di nascita (opz.)</label>
                            <input name="birth_date" type="date"
                                class="form-control @error('birth_date') is-invalid @enderror"
                                value="{{ old('birth_date') }}">
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="small text-muted">I campi contrassegnati con <span class="text-danger">*</span> sono
                        obbligatori.</div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('operator.dashboard') }}" class="btn btn-outline-secondary">Annulla</a>
                        <button class="btn my-btn-brand-primary">Crea cliente</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
