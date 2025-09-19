@extends('layouts.app')

@section('content')
    <div class="container py-4 mt-4">

        {{-- Header + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-3">Le mie richieste</h1>

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <a href="{{ route('operator.availability.requests.create') }}" class="btn my-btn-brand-primary btn-sm">
                    Nuova richiesta
                </a>
                <a href="{{ route('operator.availability.show') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-chevron-left me-1"></i> Torna all'elenco
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Stato</th>
                            <th class="text-nowrap">Effettivo da</th>
                            <th class="text-nowrap">Inviata il</th>
                            <th class="text-end text-nowrap">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                            @php
                                $status = $r->status;
                                $badgeClass = match ($status) {
                                    'pending' => 'bg-warning-subtle text-warning fw-semibold',
                                    'approved' => 'bg-success-subtle text-success fw-semibold',
                                    default => 'bg-danger-subtle text-danger fw-semibold',
                                };
                                $statusLabel = match ($status) {
                                    'pending' => 'In attesa',
                                    'approved' => 'Approvata',
                                    default => 'Respinta',
                                };
                            @endphp
                            <tr>
                                <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                                <td class="text-nowrap">
                                    {{ \Carbon\Carbon::parse($r->effective_from)->format('d/m/y') }}
                                </td>
                                <td class="text-nowrap">
                                    {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/y') }}
                                    - {{ $r->created_at->format('H:i') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('operator.availability.requests.show', $r) }}"
                                        class="btn my-btn-brand-primary btn-sm">
                                        Dettagli
                                    </a>
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

            @if ($requests instanceof \Illuminate\Contracts\Pagination\Paginator && $requests->hasPages())
                <div class="card-footer">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
