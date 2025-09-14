@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Macchine</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.machines.create') }}" class="btn btn-primary mb-3">Nuova Macchina</a>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Sala</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($machines as $machine)
                    <tr>
                        <td>{{ $machine->name }}</td>
                        <td>
                            @if ($machine->room)
                                <a href="{{ route('rooms.show', $machine->room) }}">{{ $machine->room->name }}</a>
                            @else
                                <em>—</em>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('machines.show', $machine) }}" class="btn btn-sm btn-info">Vedi</a>
                            @if (auth()->user()->hasRole('admin'))
                                <a href="{{ route('admin.machines.edit', $machine) }}"
                                    class="btn btn-sm btn-warning">Modifica</a>
                                <form action="{{ route('admin.machines.destroy', $machine) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"
                                        onclick="return confirm('Eliminare la macchina?')">Elimina</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Nessuna macchina trovata.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $machines->links() }}
    </div>
@endsection
