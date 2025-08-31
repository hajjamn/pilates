@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Pacchetti</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.packages.create') }}" class="btn btn-primary mb-3">Nuovo Pacchetto</a>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Lezioni</th>
                    <th>Prezzo</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $package)
                    <tr>
                        <td>{{ $package->name }}</td>
                        <td>{{ $package->total_lessons }}</td>
                        <td>€ {{ number_format($package->price, 2, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('packages.show', $package) }}" class="btn btn-sm btn-info">Vedi</a>
                            @if (auth()->user()->hasRole('admin'))
                                <a href="{{ route('admin.packages.edit', $package) }}"
                                    class="btn btn-sm btn-warning">Modifica</a>
                                <form action="{{ route('admin.packages.destroy', $package) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"
                                        onclick="return confirm('Eliminare il pacchetto?')">Elimina</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Nessun pacchetto trovato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $packages->links() }}
    </div>
@endsection
