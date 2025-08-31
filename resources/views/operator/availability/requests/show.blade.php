@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Richiesta #{{ $acr->id }}</h1>
            <a href="{{ route('operator.availability.requests.index') }}" class="btn btn-outline-secondary">Torna</a>
        </div>

        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Stato</div>
                    <div class="fw-semibold">
                        @if ($acr->status === 'pending')
                            <span class="badge bg-warning text-dark">pending</span>
                        @elseif($acr->status === 'approved')
                            <span class="badge bg-success">approved</span>
                        @else
                            <span class="badge bg-danger">rejected</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Effettivo da</div>
                    <div class="fw-semibold">
                        {{ \Carbon\Carbon::parse($acr->effective_from)->isoFormat('dddd D MMMM YYYY') }}</div>
                </div>
                @if ($acr->reviewed_by)
                    <div class="col-md-3">
                        <div class="text-muted small">Revisione</div>
                        <div class="fw-semibold">
                            {{ \Carbon\Carbon::parse($acr->reviewed_at)->isoFormat('D MMM YYYY HH:mm') }}
                        </div>
                    </div>
                @endif
                @if ($acr->reason)
                    <div class="col-md-12">
                        <div class="text-muted small">Nota</div>
                        <div class="fw-semibold">{{ $acr->reason }}</div>
                    </div>
                @endif
            </div>
            <div class="card-footer small">
                <span class="me-3">Aggiunte: <strong>{{ $summary['added'] }}</strong></span>
                <span class="me-3">Rimosse: <strong>{{ $summary['removed'] }}</strong></span>
                <span class="me-3">Cambiate: <strong>{{ $summary['changed'] }}</strong></span>
                <span>Invariate: <strong>{{ $summary['unchanged'] }}</strong></span>
            </div>
        </div>

        @foreach (range(0, 6) as $day)
            <div class="card mb-4">
                <div class="card-header fw-semibold">{{ ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'][$day] }}</div>
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
                                    <tr
                                        class="
                  @if ($row['status'] === 'added') table-success
                  @elseif($row['status'] === 'removed') table-danger
                  @elseif($row['status'] === 'changed') table-warning
                  @else @endif
                ">
                                        <th>{{ $h }}</th>
                                        <td class="text-center">{{ $row['from'] }}</td>
                                        <td class="text-center">{{ $row['to'] }}</td>
                                        <td class="text-center text-capitalize">{{ $row['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
