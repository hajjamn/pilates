@extends('layouts.app')

@section('page-title', 'Sale')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">
        {{-- Titolo --}}
        <div class="mb-2">
            <h1 class="h4 m-0">Sale</h1>
        </div>

        {{-- Barra strumenti --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.rooms.create') }}" class="btn btn-sm my-btn-brand-primary">
                    + Nuova sala
                </a>
            @endif
        </div>

        {{-- Lista sale --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Nome</th>
                            <th>Descrizione</th>
                            <th class="text-end" style="width:1%">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('rooms.show', $room) }}" class="text-decoration-none">
                                        {{ $room->name }}
                                    </a>
                                </td>
                                <td>{{ $room->description }}</td>
                                <td class="text-end">
                                    @if (auth()->user()->hasRole('admin'))
                                        <a href="{{ route('admin.rooms.edit', $room) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen me-1"></i> Modifica
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger open-delete-room-modal"
                                            data-room-id="{{ $room->id }}" data-room-name="{{ $room->name }}"
                                            data-bs-toggle="modal" data-bs-target="#deleteRoomModal">
                                            <i class="fa-solid fa-trash-can me-1"></i> Elimina
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted text-center p-4">Nessuna sala trovata.</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Modale eliminazione --}}
    @once
        <div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-labelledby="deleteRoomModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="deleteRoomForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteRoomModalLabel">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                            Conferma eliminazione
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
    @endonce

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modalEl = document.getElementById('deleteRoomModal');
                const form = document.getElementById('deleteRoomForm');
                const nameEl = document.getElementById('delete-room-name');

                modalEl.addEventListener('show.bs.modal', event => {
                    const btn = event.relatedTarget;
                    if (!btn) return;
                    const roomId = btn.getAttribute('data-room-id');
                    const roomName = btn.getAttribute('data-room-name') || 'Sala';

                    form.action = "{{ route('admin.rooms.destroy', '__id__') }}".replace('__id__', roomId);
                    nameEl.textContent = roomName;
                });
            });
        </script>
    @endpush
@endsection
