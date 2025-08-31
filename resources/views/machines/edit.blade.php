@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Modifica Macchina: {{ $machine->name }}</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.machines.update', $machine) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Nome</label>
                <input type="text" class="form-control" id="name" name="name"
                    value="{{ old('name', $machine->name) }}" required>
            </div>

            <div class="mb-3">
                <label for="room_id" class="form-label">Sala</label>
                <select class="form-select" id="room_id" name="room_id" required>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(old('room_id', $machine->room_id) == $room->id)>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
                @if (!$rooms->count())
                    <small class="text-muted">Nessuna sala disponibile: crea prima una sala.</small>
                @endif
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrizione</label>
                <textarea class="form-control" id="description" name="description">{{ old('description', $machine->description) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Aggiorna</button>
            <a href="{{ route('machines.show', $machine) }}" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
@endsection
