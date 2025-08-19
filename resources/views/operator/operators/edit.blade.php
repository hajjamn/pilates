@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Modifica operatore: {{ $operator->full_name }}</h1>

        {{-- Messaggi di stato / errori --}}
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Correggi i seguenti errori:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('operator.operators.update', $operator) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        {{-- Nome --}}
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" name="first_name"
                                class="form-control @error('first_name') is-invalid @enderror"
                                value="{{ old('first_name', $operator->first_name) }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Cognome --}}
                        <div class="col-md-6">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="last_name"
                                class="form-control @error('last_name') is-invalid @enderror"
                                value="{{ old('last_name', $operator->last_name) }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $operator->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Telefono --}}
                        <div class="col-md-6">
                            <label class="form-label">Telefono</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $operator->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Data di nascita --}}
                        <div class="col-md-4">
                            <label class="form-label">Data di nascita</label>
                            <input type="date" name="birth_date"
                                class="form-control @error('birth_date') is-invalid @enderror"
                                value="{{ old('birth_date', optional($operator->birth_date)->format('Y-m-d')) }}">
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Ruoli (solo admin) --}}
                        @if ($roles->isNotEmpty())
                            <div class="col-md-12">
                                <label class="form-label d-block">Ruoli</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($roles as $role)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]"
                                                value="{{ $role->name }}" id="role_{{ $role->id }}"
                                                {{ in_array($role->name, old('roles', $operator->getRoleNames()->toArray())) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                {{ ucfirst($role->name) }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Bottoni --}}
                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('operator.operators.show', $operator) }}" class="btn btn-secondary">Annulla</a>
                        <button type="submit" class="btn btn-primary">Salva modifiche</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
