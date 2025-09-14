@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Crea nuova Sala</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.rooms.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Nome</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrizione</label>
                <textarea class="form-control" id="description" name="description">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label for="max_clients" class="form-label">Capienza massima</label>
                <input type="number" id="max_clients" name="max_clients"
                    class="form-control @error('max_clients') is-invalid @enderror" min="1" max="100"
                    step="1" required value="{{ old('max_clients') }}" inputmode="numeric">
                <div class="form-text">Valori consentiti: 1–100.</div>
                @error('max_clients')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            <button type="submit" class="btn btn-primary">Salva</button>
            <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
@endsection
