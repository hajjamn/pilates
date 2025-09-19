@extends('layouts.app')

@section('page-title', 'Pacchetti')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">
        {{-- Titolo --}}
        <div class="mb-2">
            <h1 class="h4 m-0">Pacchetti</h1>
        </div>

        {{-- Barra strumenti --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.packages.create') }}" class="btn btn-sm my-btn-brand-primary">
                    + Nuovo pacchetto
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
                            <th class="text-center">Lezioni</th>
                            <th class="text-end">Prezzo</th>
                            @if (auth()->user()->hasRole('admin'))
                                <th class="text-end" style="width:1%">Azioni</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packages as $package)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('packages.show', $package) }}" class="text-decoration-none">
                                        {{ $package->name }}
                                    </a>
                                </td>
                                <td class="text-center">{{ $package->total_lessons }}</td>
                                <td class="text-end">€ {{ number_format($package->price, 2, ',', '.') }}</td>

                                @if (auth()->user()->hasRole('admin'))
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                            data-bs-target="#deletePackageModal" data-package-id="{{ $package->id }}"
                                            data-package-name="{{ $package->name }}">
                                            <i class="fa-solid fa-trash-can me-1"></i> Elimina
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->hasRole('admin') ? 4 : 3 }}"
                                    class="text-muted text-center p-4">
                                    Nessun pacchetto trovato.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $packages->links() }}
        </div>
    </div>

    {{-- Modale eliminazione --}}
    @once
        <div class="modal fade" id="deletePackageModal" tabindex="-1" aria-labelledby="deletePackageModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="deletePackageForm">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title" id="deletePackageModalLabel">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                            Conferma eliminazione pacchetto
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        <p>Vuoi davvero eliminare questo pacchetto?</p>
                        <p class="mb-0"><strong id="delete-package-name">—</strong></p>
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
                    const modalEl = document.getElementById('deletePackageModal');
                    const form = document.getElementById('deletePackageForm');
                    const nameEl = document.getElementById('delete-package-name');

                    modalEl.addEventListener('show.bs.modal', (event) => {
                        const btn = event.relatedTarget;
                        if (!btn) return;

                        const id = btn.getAttribute('data-package-id');
                        const name = btn.getAttribute('data-package-name') || 'Pacchetto';

                        form.action = "{{ route('admin.packages.destroy', '__id__') }}".replace('__id__', id);
                        nameEl.textContent = name;
                    });
                });
            </script>
        @endpush
    @endonce
@endsection
