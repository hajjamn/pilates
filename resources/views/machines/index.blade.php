@extends('layouts.app')

@section('page-title', 'Macchine')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">
        {{-- Titolo --}}
        <div class="mb-2">
            <h1 class="h4 m-0">Macchine</h1>
        </div>

        {{-- Barra strumenti --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.machines.create') }}" class="btn btn-sm my-btn-brand-primary">
                    + Nuova macchina
                </a>
            @endif
        </div>

        {{-- Lista --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Nome</th>
                            <th>Sala</th>
                            @if (auth()->user()->hasRole('admin'))
                                <th class="text-end" style="width:1%">Azioni</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($machines as $machine)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('machines.show', $machine) }}" class="text-decoration-none">
                                        {{ $machine->name }}
                                    </a>
                                </td>
                                <td>
                                    @if ($machine->room)
                                        <a href="{{ route('rooms.show', $machine->room) }}" class="text-decoration-none">
                                            {{ $machine->room->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                @if (auth()->user()->hasRole('admin'))
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteMachineModal" data-machine-id="{{ $machine->id }}"
                                            data-machine-name="{{ $machine->name }}">
                                            <i class="fa-solid fa-trash-can me-1"></i> Elimina
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->hasRole('admin') ? 3 : 2 }}"
                                    class="text-muted text-center p-4">
                                    Nessuna macchina trovata.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $machines->links() }}
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
