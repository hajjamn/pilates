@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Pacchetto: {{ $package->name }}</h1>

        <p><strong>Numero lezioni:</strong> {{ $package->total_lessons }}</p>
        <p><strong>Prezzo:</strong> € {{ number_format($package->price, 2, ',', '.') }}</p>

        <a href="{{ route('packages.index') }}" class="btn btn-secondary">Torna alla lista</a>

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-warning">Modifica</a>
            <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" onclick="return confirm('Eliminare il pacchetto?')">Elimina</button>
            </form>
        @endif
    </div>
@endsection
