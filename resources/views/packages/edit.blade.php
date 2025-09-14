@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Modifica Pacchetto: {{ $package->name }}</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.packages.update', $package) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Nome</label>
                <input type="text" class="form-control" id="name" name="name"
                    value="{{ old('name', $package->name) }}" required>
            </div>

            <div class="mb-3">
                <label for="total_lessons" class="form-label">Numero lezioni</label>
                <input type="number" class="form-control" id="total_lessons" name="total_lessons" min="1"
                    step="1" value="{{ old('total_lessons', $package->total_lessons) }}" required>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Prezzo (€)</label>
                <input type="number" class="form-control" id="price" name="price" min="0" step="0.01"
                    value="{{ old('price', $package->price) }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Aggiorna</button>
            <a href="{{ route('packages.show', $package) }}" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
@endsection
