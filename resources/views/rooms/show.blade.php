@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Sala: {{ $room->name }}</h1>

        <p><strong>Capienza massima:</strong> {{ $room->max_clients }}</p>


        <p><strong>Descrizione:</strong> {{ $room->description }}</p>

        <hr>

        <h3>Macchine in questa sala</h3>

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.machines.create', ['room_id' => $room->id]) }}" class="btn btn-sm btn-primary mb-3">
                Nuova macchina in questa sala
            </a>
        @endif

        @if ($room->machines->count() > 0)
            <ul class="list-group mb-3">
                @foreach ($room->machines as $machine)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('machines.show', $machine) }}">
                            {{ $machine->name }}
                        </a>
                        @if (auth()->user()->hasRole('admin'))
                            <div>
                                <a href="{{ route('admin.machines.edit', $machine) }}"
                                    class="btn btn-sm btn-warning">Modifica</a>
                                <form action="{{ route('admin.machines.destroy', $machine) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"
                                        onclick="return confirm('Eliminare la macchina?')">
                                        Elimina
                                    </button>
                                </form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p>Nessuna macchina presente in questa sala.</p>
        @endif

        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Torna alla lista sale</a>

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-warning">Modifica sala</a>
            <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" onclick="return confirm('Eliminare la sala?')">Elimina sala</button>
            </form>
        @endif
    </div>
@endsection
