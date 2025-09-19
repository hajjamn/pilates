@extends('layouts.app')

@section('page-title', 'Richieste cambio disponibilità')

@section('content')
    <div class="container py-4" style="max-width:1000px;">

        {{-- Titolo + back --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Richieste di cambio disponibilità</h1>
            <a href="{{ route('admin.availability.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Torna alle disponibilità
            </a>
        </div>

        {{-- Tabella --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Operatore</th>
                            <th>Stato</th>
                            <th>Eff. da</th>
                            <th>Inviata il</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                            @php
                                $name =
                                    trim(($r->operator->first_name ?? '') . ' ' . ($r->operator->last_name ?? '')) ?:
                                    'Operatore #' . $r->operator_id;
                            @endphp
                            <tr class="table-row-link"
                                onclick="window.location='{{ route('admin.availability.requests.show', $r) }}'">
                                <td>{{ $name }}</td>
                                <td>
                                    @if ($r->status === 'pending')
                                        <span class="badge bg-warning text-dark text-uppercase">In Attesa</span>
                                    @elseif($r->status === 'approved')
                                        <span class="badge bg-success text-uppercase">Approvata</span>
                                    @else
                                        <span class="badge bg-danger text-uppercase">Respinta</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($r->effective_from)->isoFormat('D MMM YYYY') }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->created_at)->isoFormat('D MMM YYYY HH:mm') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">Nessuna richiesta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginazione --}}
        <div class="mt-3">
            {{ $requests->links() }}
        </div>
    </div>
@endsection
