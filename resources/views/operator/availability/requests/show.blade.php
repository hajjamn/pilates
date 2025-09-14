@extends('layouts.app')

@section('content')
    @php
        $badgePalette = [
            'bg-primary',
            'bg-secondary',
            'bg-success',
            'bg-danger',
            'bg-warning text-dark',
            'bg-info text-dark',
            'bg-dark',
            'bg-light text-dark',
        ];
        $roomBadgeClass = [];
        $i = 0;
        foreach ($legend ?? [] as $rid => $info) {
            $roomBadgeClass[$rid] = $badgePalette[$i % count($badgePalette)];
            $i++;
        }

        $statusIt = [
            'added' => 'Aggiunta',
            'removed' => 'Rimossa',
            'changed' => 'Modificata',
            'unchanged' => 'Invariata',
        ];
    @endphp

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Richiesta #{{ $acr->id }}</h1>
            <a href="{{ route('operator.availability.requests.index') }}" class="btn btn-outline-secondary">Torna</a>
        </div>

        @if (!empty($legend))
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                @foreach ($legend as $rid => $info)
                    <span class="badge {{ $roomBadgeClass[$rid] ?? 'bg-secondary' }}"
                        title="{{ $info['name'] }}">{{ $info['abbr'] }}</span>
                    <span class="me-3">{{ $info['name'] }}</span>
                @endforeach
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Stato</div>
                    <div class="fw-semibold">
                        @if ($acr->status === 'pending')
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($acr->status === 'approved')
                            <span class="badge bg-success">Approvata</span>
                        @else
                            <span class="badge bg-danger">Respinta</span>
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
    </div>
@endsection
