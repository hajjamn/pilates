@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Macchina: {{ $machine->name }}</h1>

        <p><strong>Sala:</strong>
            @if ($machine->room)
                <a href="{{ route('rooms.show', $machine->room) }}">{{ $machine->room->name }}</a>
            @else
                <em>—</em>
            @endif
        </p>

        <p><strong>Descrizione:</strong> {{ $machine->description }}</p>

        <a href="{{ route('machines.index') }}" class="btn btn-secondary">Torna alla lista</a>

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.machines.edit', $machine) }}" class="btn btn-warning">Modifica</a>
            <form action="{{ route('admin.machines.destroy', $machine) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" onclick="return confirm('Eliminare la macchina?')">Elimina</button>
            </form>
        @endif
    </div>
@endsection
