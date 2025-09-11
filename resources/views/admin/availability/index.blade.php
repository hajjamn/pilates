@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Disponibilità settimanali — panoramica</h1>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <a href="{{ route('operator.availability.show') }}" class="btn btn-outline-secondary btn-sm">
                Mie Disponibilità
            </a>
            <a href="{{ route('admin.availability.requests.index') }}" class="btn btn-outline-secondary btn-sm">
                Richieste Cambio Disponibilità
            </a>
            <a href="{{ route('admin.availability.generate.form') }}" class="btn btn-outline-primary btn-sm ms-auto">
                Genera lezioni
            </a>
        </div>


        <form method="GET" action="{{ route('admin.availability.index') }}" class="card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-auto">
                    <a class="btn btn-outline-secondary"
                        href="{{ route('admin.availability.index', ['week' => $prev_week]) }}">&laquo; Settimana
                        precedente</a>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Settimana</label>
                    <input type="date" name="week" class="form-control" value="{{ $week_start }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Vai</button>
                </div>
                <div class="col-auto ms-auto">
                    <a class="btn btn-outline-secondary"
                        href="{{ route('admin.availability.index', ['week' => $next_week]) }}">Settimana successiva
                        &raquo;</a>
                </div>
            </div>
            <div class="card-footer small text-muted">
                {{ \Carbon\Carbon::parse($week_start)->isoFormat('D MMM') }} –
                {{ \Carbon\Carbon::parse($week_end)->isoFormat('D MMM YYYY') }}
            </div>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Lezioni scoperte</div>
                        <div class="fs-4">{{ $health_counts['uncovered'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Conflitti disponibilità</div>
                        <div class="fs-4">{{ $health_counts['conflicts'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Slot occupati da lezioni</div>
                        <div class="fs-4">{{ $health_counts['occupied'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($days as $d)
            @php $date = $day_date[$d['key']] ?? null; @endphp
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    {{ $d['label'] }} @if ($date)
                        — {{ \Carbon\Carbon::parse($date)->isoFormat('D MMM') }}
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">Ora</th>
                                    @foreach ($rooms as $room)
                                        <th class="text-center">{{ $room['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            <tbody>
                                @foreach ($hours as $hour)
                                    <tr>
                                        <th>{{ $hour }}</th>

                                        @foreach ($rooms as $room)
                                            @php
                                                $roomId = $room['id'];
                                                $ops = $matrix[$d['key']][$hour][$roomId] ?? [];
                                                $isConflict = $conflict_map[$d['key']][$hour][$roomId] ?? false;
                                                $isOccupied = !empty($occupied[$date][$hour][$roomId] ?? []);
                                            @endphp

                                            <td class="text-center">
                                                @if ($isOccupied)
                                                    <span class="badge bg-warning text-dark me-1">Lezione</span>
                                                @endif
                                                @if ($isConflict)
                                                    <span class="badge bg-danger me-1">Confl.</span>
                                                @endif

                                                @if (empty($ops))
                                                    <span class="text-muted">—</span>
                                                @else
                                                    @foreach ($ops as $op)
                                                        <a href="{{ route('operator.operators.show', $op['id']) }}"
                                                            class="d-inline-block me-2">
                                                            {{ $op['name'] }}
                                                        </a>
                                                    @endforeach
                                                @endif
                                            </td>
                                        @endforeach

                                    </tr>
                                @endforeach
                            </tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        @if (!empty($uncovered))
            <div class="card">
                <div class="card-header">Lezioni scoperte nella settimana selezionata</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Ora</th>
                                    <th>Sala</th>
                                    <th>Operatore</th>
                                    <th>Lesson ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $roomNames = collect($rooms)->pluck('label', 'id');
                                @endphp
                                @foreach ($uncovered as $u)
                                    <tr>
                                        <td>{{ $u['date'] }}</td>
                                        <td>{{ $u['time'] }}</td>
                                        <td>{{ $roomNames[$u['room_id']] ?? 'Sala ' . $u['room_id'] }}</td>
                                        <td>#{{ $u['operator_id'] }} — {{ $u['operator_name'] }}</td>
                                        <td>{{ $u['lesson_id'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
