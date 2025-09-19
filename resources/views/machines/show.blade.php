@extends('layouts.app')

@section('page-title', 'Macchina: ' . $machine->name)

@section('content')
    <div class="container mt-4" style="max-width:1000px;">

        {{-- Header + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Macchina: {{ $machine->name }}</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('machines.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-chevron-left me-1"></i> Indietro
                </a>
                @if (auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.machines.edit', $machine) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-pen me-1"></i> Modifica
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                        data-bs-target="#deleteMachineModal" data-machine-id="{{ $machine->id }}"
                        data-machine-name="{{ $machine->name }}">
                        <i class="fa-solid fa-trash-can me-1"></i> Elimina
                    </button>
                @endif
            </div>
        </div>

        {{-- Card info macchina --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <strong>Informazioni principali</strong>
            </div>
            <div class="card-body row g-3 small">
                <div class="col-12 col-md-6">
                    <div class="text-muted">Nome</div>
                    <div class="fw-semibold">{{ $machine->name }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-muted">Sala</div>
                    <div class="fw-semibold">
                        @if ($machine->room)
                            <a href="{{ route('rooms.show', $machine->room) }}" class="text-decoration-none">
                                {{ $machine->room->name }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
                <div class="col-12">
                    <div class="text-muted">Descrizione</div>
                    <div>{{ $machine->description ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale eliminazione --}}
    @once
        <div class="modal fade" id="deleteMachineModal" tabindex="-1" aria-labelledby="deleteMachineModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="deleteMachineForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteMachineModalLabel">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                            Conferma eliminazione macchina
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        <p>Vuoi davvero eliminare questa macchina?</p>
                        <p class="mb-0"><strong id="delete-machine-name">—</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-danger">Elimina</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const modalEl = document.getElementById('deleteMachineModal');
                    const form = document.getElementById('deleteMachineForm');
                    const nameEl = document.getElementById('delete-machine-name');

                    modalEl.addEventListener('show.bs.modal', (event) => {
                        const btn = event.relatedTarget;
                        if (!btn) return;

                        const id = btn.getAttribute('data-machine-id');
                        const name = btn.getAttribute('data-machine-name') || 'Macchina';

                        form.action = "{{ route('admin.machines.destroy', '__id__') }}".replace('__id__', id);
                        nameEl.textContent = name;
                    });
                });
            </script>
        @endpush
    @endonce
@endsection
