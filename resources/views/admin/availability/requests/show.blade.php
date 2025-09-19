@extends('layouts.app')

@section('page-title', 'Richiesta #' . $acr->id)

@section('content')
    <div class="container py-4" style="max-width:1000px;">

        {{-- Titolo + back --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h4 mb-0">Richiesta #{{ $acr->id }} — {{ $operatorName }}</h1>
            <a href="{{ route('admin.availability.requests.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Torna alla lista
            </a>
        </div>

        @php
            $statusIt = [
                'added' => 'Aggiunta',
                'removed' => 'Rimossa',
                'changed' => 'Modificata',
                'unchanged' => 'Invariata',
            ];
        @endphp

        {{-- Info principali --}}
        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Stato</div>
                    <div class="fw-semibold">
                        @if ($acr->status === 'pending')
                            <span class="badge bg-warning text-dark text-uppercase">In Attesa</span>
                        @elseif($acr->status === 'approved')
                            <span class="badge bg-success text-uppercase">Approvata</span>
                        @else
                            <span class="badge bg-danger text-uppercase">Respinta</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Effettivo da</div>
                    <div class="fw-semibold">
                        {{ \Carbon\Carbon::parse($acr->effective_from)->isoFormat('dddd D MMMM YYYY') }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Inviata il</div>
                    <div class="fw-semibold">
                        {{ \Carbon\Carbon::parse($acr->created_at)->isoFormat('D MMM YYYY HH:mm') }}
                    </div>
                </div>
            </div>
            <div class="card-footer small">
                <span class="me-3">Aggiunte: <strong>{{ $summary['added'] }}</strong></span>
                <span class="me-3">Rimosse: <strong>{{ $summary['removed'] }}</strong></span>
                <span class="me-3">Cambiate: <strong>{{ $summary['changed'] }}</strong></span>
                <span>Invariate: <strong>{{ $summary['unchanged'] }}</strong></span>
            </div>
        </div>

        {{-- Tabelle giornaliere --}}
        @foreach (range(0, 6) as $day)
            <div class="card mb-4">
                <div class="card-header fw-semibold">{{ $days_labels[$day] }}</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">Ora</th>
                                    <th class="text-center">Attuale</th>
                                    <th class="text-center">Proposto</th>
                                    <th class="text-center">Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hours as $h)
                                    @php $row = $diff[$day][$h]; @endphp
                                    <tr @class([
                                        'table-success' => $row['status'] === 'added',
                                        'table-danger' => $row['status'] === 'removed',
                                        'table-warning' => $row['status'] === 'changed',
                                    ])>
                                        <th>{{ $h }}</th>
                                        <td class="text-center">{{ $row['from'] }}</td>
                                        <td class="text-center">{{ $row['to'] }}</td>
                                        <td class="text-center text-capitalize">
                                            {{ $statusIt[$row['status']] ?? ucfirst($row['status']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Alert --}}
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Azioni (solo se pending) --}}
        @if ($acr->status === 'pending')
            <div class="card mb-4">
                <div class="card-header fw-semibold">Azione</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Motivazione (opzionale)</label>
                        <input type="text" id="acr-reason" class="form-control"
                            placeholder="Es. allineamento con coperture sala">
                    </div>

                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.availability.requests.approve', $acr) }}">
                            @csrf
                            <input type="hidden" name="reason" id="reason-approve">
                            <button class="btn btn-success"
                                onclick="document.getElementById('reason-approve').value = document.getElementById('acr-reason').value;">
                                Approva e applica
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.availability.requests.reject', $acr) }}">
                            @csrf
                            <input type="hidden" name="reason" id="reason-reject">
                            <button class="btn btn-outline-danger"
                                onclick="document.getElementById('reason-reject').value = document.getElementById('acr-reason').value;">
                                Rifiuta
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Back button in fondo --}}
        <div class="mt-3">
            <a href="{{ route('admin.availability.requests.index') }}" class="btn btn-sm btn-outline-secondary">
                ← Torna alla lista
            </a>
        </div>
    </div>
@endsection
