@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Sale</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary mb-3">Nuova Sala</a>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                    <tr>
                        <td>{{ $room->name }}</td>
                        <td>{{ $room->description }}</td>
                        <td>
                            <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-info">Vedi</a>
                            @if (auth()->user()->hasRole('admin'))
                                <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-sm btn-warning">Modifica</a>
                                <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Eliminare la sala?')">
                                        Elimina
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Nessuna sala trovata.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
