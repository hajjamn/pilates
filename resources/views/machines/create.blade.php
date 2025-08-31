@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Crea nuova Macchina</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.machines.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Nome</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="mb-3">
                <label for="room_id" class="form-label">Sala</label>
                <select class="form-select" id="room_id" name="room_id" required>
                    <option value="">— Seleziona sala —</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected(old('room_id', $selectedRoomId ?? null) == $room->id)>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrizione</label>
                <textarea class="form-control" id="description" name="description">{{ old('description') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Salva</button>
            <a href="{{ route('machines.index') }}" class="btn btn-secondary">Annulla</a>
        </form>

    </div>
@endsection
