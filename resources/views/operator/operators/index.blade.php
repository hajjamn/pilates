@extends('layouts.app')

@section('page-title', 'Operatori')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">
        {{-- Titolo --}}
        <div class="mb-2">
            <h1 class="h4 m-0">Operatori</h1>
        </div>

        {{-- Barra strumenti sotto al titolo --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('operator.operators.create') }}" class="btn btn-sm my-btn-brand-primary">
                    + Crea nuovo operatore
                </a>
            @endif
        </div>

        {{-- Nessun operatore --}}
        @if ($operators->isEmpty())
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>Nessun operatore presente.</span>
                @if (auth()->user()->hasRole('admin'))
                    <a href="{{ route('operator.operators.create') }}" class="btn btn-sm btn-outline-primary">
                        Crea il primo
                    </a>
                @endif
            </div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle m-0">
                        <thead>
                            <tr class="small text-muted">
                                <th>Operatore</th>
                                <th class="text-center" style="width:1%">Contatti</th>
                                <th>Ruoli</th>
                                @if (auth()->user()->hasRole('admin'))
                                    <th class="text-end" style="width:1%">Azioni</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($operators as $operator)
                                @php
                                    $fullName =
                                        $operator->full_name ?: $operator->email ?: 'Operatore #' . $operator->id;

                                    $digits = $operator->phone ? preg_replace('/\D+/', '', $operator->phone) : null;
                                    if ($digits && str_starts_with($digits, '0')) {
                                        $digits = '39' . $digits; // prefisso IT
                                    }
                                    $waText = rawurlencode('Ciao ' . $operator->full_name . '!');
                                    $waLink = $digits ? 'https://wa.me/' . $digits . '?text=' . $waText : null;
                                @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        <a href="{{ route('operator.operators.show', $operator) }}"
                                            class="text-decoration-none">
                                            {{ $fullName }}
                                        </a>
                                    </td>

                                    <td class="text-center">
                                        <div class="dropdown d-inline-block" data-bs-display="static">
                                            <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fa-solid fa-address-card fa-lg"></i>
                                                <span class="visually-hidden">Contatti</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if ($operator->email)
                                                    <li>
                                                        <a class="dropdown-item text-primary"
                                                            href="mailto:{{ $operator->email }}">
                                                            <i class="fa-solid fa-envelope me-2"></i>{{ $operator->email }}
                                                        </a>
                                                    </li>
                                                @endif

                                                @if ($waLink)
                                                    <li>
                                                        <a class="dropdown-item text-whatsapp" href="{{ $waLink }}"
                                                            target="_blank" rel="noopener">
                                                            <i class="fa-brands fa-whatsapp me-2"></i>{{ $operator->phone }}
                                                        </a>
                                                    </li>
                                                @elseif ($operator->phone)
                                                    <li>
                                                        <span class="dropdown-item-text">
                                                            <i class="fa-solid fa-phone me-2"></i>{{ $operator->phone }}
                                                        </span>
                                                    </li>
                                                @endif

                                                @unless ($operator->email || $operator->phone)
                                                    <li>
                                                        <span class="dropdown-item-text text-muted">Nessun contatto</span>
                                                    </li>
                                                @endunless
                                            </ul>
                                        </div>
                                    </td>

                                    <td>
                                        {{ implode(', ', $operator->getRoleNames()->toArray()) }}
                                    </td>

                                    @if (auth()->user()->hasRole('admin'))
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger open-delete-modal"
                                                data-bs-toggle="modal" data-bs-target="#deleteOperatorModal"
                                                data-operator-id="{{ $operator->id }}"
                                                data-operator-name="{{ $fullName }}">
                                                <i class="fa-solid fa-trash-can me-1"></i> Elimina
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @endif
    </div>

    @once
        <div class="modal fade" id="deleteOperatorModal" tabindex="-1" aria-labelledby="deleteOperatorModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content" id="deleteOperatorForm">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteOperatorModalLabel">Conferma eliminazione</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>

                    <div class="modal-body">
                        <p>Sei sicuro di voler eliminare questo operatore?</p>
                        <p class="mb-0"><strong id="delete-operator-name">—</strong></p>
                        <div class="small text-muted mt-2">
                            L’operazione è reversibile (soft delete).
                        </div>
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
                const modalEl = document.getElementById('deleteOperatorModal');
                if (!modalEl) return;

                const form = document.getElementById('deleteOperatorForm');
                const nameEl = document.getElementById('delete-operator-name');

                // Quando il modal sta per aprirsi, Bootstrap passa il bottone che l'ha triggerato in event.relatedTarget
                modalEl.addEventListener('show.bs.modal', (event) => {
                    const btn = event.relatedTarget;
                    if (!btn) return;

                    const operatorId = btn.getAttribute('data-operator-id');
                    const operatorName = btn.getAttribute('data-operator-name') || 'Operatore';

                    form.action = "{{ route('operator.operators.destroy', '__id__') }}".replace('__id__',
                        operatorId);
                    nameEl.textContent = operatorName;
                });
            });
        </script>
    @endpush



@endsection
