@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Le mie richieste</h1>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('operator.availability.requests.create') }}" class="btn btn-primary">Nuova richiesta</a>
                <a href="{{ route('operator.availability.show') }}" class="ms-3 btn btn-outline-secondary">Torna alla
                    vista</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Stato</th>
                            <th>Effettivo da</th>
                            <th>Inviata il</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                            <tr>
                                <td>{{ $r->id }}</td>
                                <td>
                                    @if ($r->status === 'pending')
                                        <span class="badge bg-warning text-dark">pending</span>
                                    @elseif($r->status === 'approved')
                                        <span class="badge bg-success">approved</span>
                                    @else
                                        <span class="badge bg-danger">rejected</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($r->effective_from)->isoFormat('D MMM YYYY') }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->created_at)->isoFormat('D MMM YYYY HH:mm') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('operator.availability.requests.show', $r) }}"
                                        class="btn btn-sm btn-outline-primary">Dettaglio</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">Nessuna richiesta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
@endsection
