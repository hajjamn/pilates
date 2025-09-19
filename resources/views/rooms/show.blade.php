@extends('layouts.app')

@section('page-title', 'Sala: ' . $room->name)

@section('content')
    <div class="container mt-4" style="max-width:1000px;">

        {{-- Header + azioni --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Sala: {{ $room->name }}</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('rooms.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-chevron-left me-1"></i> Indietro
                </a>
                @if (auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-pen me-1"></i> Modifica
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger open-delete-room-modal"
                        data-room-id="{{ $room->id }}" data-room-name="{{ $room->name }}" data-bs-toggle="modal"
                        data-bs-target="#deleteRoomModal">
                        <i class="fa-solid fa-trash-can me-1"></i> Elimina
                    </button>
                @endif
            </div>
        </div>

        {{-- Card info sala --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
                <strong>Informazioni principali</strong>
            </div>
            <div class="card-body row g-3 small">
                <div class="col-12 col-md-6">
                    <div class="text-muted">Nome</div>
                    <div class="fw-semibold">{{ $room->name }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-muted">Capienza massima</div>
                    <div class="fw-semibold">{{ $room->max_clients }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted">Descrizione</div>
                    <div>{{ $room->description ?: '—' }}</div>
                </div>
            </div>
        </div>

        {{-- Card macchine --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <strong>Macchine in questa sala</strong>
                @if (auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.machines.create', ['room_id' => $room->id]) }}"
                        class="btn btn-sm my-btn-brand-primary">
                        + Nuova macchina
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($room->machines->count() > 0)
                    <ul class="list-group list-group-flush">
                        @foreach ($room->machines as $machine)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('machines.show', $machine) }}" class="fw-semibold text-decoration-none">
                                    {{ $machine->name }}
                                </a>
                                @if (auth()->user()->hasRole('admin'))
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.machines.edit', $machine) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger open-delete-machine-modal"
                                            data-machine-id="{{ $machine->id }}" data-machine-name="{{ $machine->name }}"
                                            data-bs-toggle="modal" data-bs-target="#deleteMachineModal">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="p-3 text-muted fst-italic">Nessuna macchina presente in questa sala.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modale eliminazione sala --}}
    @once
        <div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-labelledby="deleteRoomModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="deleteRoomForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteRoomModalLabel">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Conferma eliminazione sala
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        <p>Vuoi davvero eliminare questa sala?</p>
                        <p class="mb-0"><strong id="delete-room-name">—</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-danger">Elimina</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modale eliminazione macchina --}}
        <div class="modal fade" id="deleteMachineModal" tabindex="-1" aria-labelledby="deleteMachineModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="deleteMachineForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteMachineModalLabel">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Conferma eliminazione macchina
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
    @endonce

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Sala
                const roomModal = document.getElementById('deleteRoomModal');
                if (roomModal) {
                    roomModal.addEventListener('show.bs.modal', event => {
                        const btn = event.relatedTarget;
                        const form = document.getElementById('deleteRoomForm');
                        const nameEl = document.getElementById('delete-room-name');
                        form.action = "{{ route('admin.rooms.destroy', '__id__') }}".replace('__id__', btn
                            .dataset.roomId);
                        nameEl.textContent = btn.dataset.roomName;
                    });
                }

                // Macchina
                const machineModal = document.getElementById('deleteMachineModal');
                if (machineModal) {
                    machineModal.addEventListener('show.bs.modal', event => {
                        const btn = event.relatedTarget;
                        const form = document.getElementById('deleteMachineForm');
                        const nameEl = document.getElementById('delete-machine-name');
                        form.action = "{{ route('admin.machines.destroy', '__id__') }}".replace('__id__', btn
                            .dataset.machineId);
                        nameEl.textContent = btn.dataset.machineName;
                    });
                }
            });
        </script>
    @endpush
@endsection
