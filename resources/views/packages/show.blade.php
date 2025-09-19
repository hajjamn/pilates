@extends('layouts.app')

@section('page-title', 'Pacchetto')

@section('content')
    <div class="container mt-4" style="max-width:800px;">
        {{-- Header + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Pacchetto: {{ $package->name }}</h1>
            <a href="{{ route('packages.index') }}" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-chevron-left me-1"></i> Indietro
            </a>
        </div>

        {{-- Card dettagli --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
                <strong>Dettagli pacchetto</strong>
            </div>
            <div class="card-body row g-3 small">
                <div class="col-12 col-md-6">
                    <div class="text-muted">Numero lezioni</div>
                    <div class="fw-semibold">{{ $package->total_lessons }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-muted">Prezzo</div>
                    <div class="fw-semibold">€ {{ number_format($package->price, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Azioni admin --}}
        @if (auth()->user()->hasRole('admin'))
            <div class="d-flex gap-2">
                <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-pen me-1"></i> Modifica
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#deletePackageModal">
                    <i class="fa-solid fa-trash-can me-1"></i> Elimina
                </button>
            </div>

            {{-- Modale eliminazione --}}
            @once
                <div class="modal fade" id="deletePackageModal" tabindex="-1" aria-labelledby="deletePackageModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content" action="{{ route('admin.packages.destroy', $package) }}">
                            @csrf
                            @method('DELETE')
                            <div class="modal-header">
                                <h5 class="modal-title" id="deletePackageModalLabel">
                                    <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                                    Conferma eliminazione
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                            </div>
                            <div class="modal-body">
                                <p>Vuoi davvero eliminare il pacchetto <strong>{{ $package->name }}</strong>?</p>
                                <div class="small text-muted">L’operazione non può essere annullata.</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Annulla</button>
                                <button type="submit" class="btn btn-danger">Elimina</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endonce
        @endif
    </div>
@endsection
