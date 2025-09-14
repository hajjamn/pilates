@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Genera lezioni dalle disponibilità</h1>
        </div>

        <form method="GET" action="{{ route('admin.availability.generate.form') }}" class="card mb-4">
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Da</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">A</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-3 align-self-end">
                    <button class="btn btn-primary">Anteprima</button>
                </div>
            </div>
        </form>

        @if (session('result'))
            @php $res = session('result'); @endphp
            <div class="alert alert-success">
                Creati: <strong>{{ count($res['created_ids']) }}</strong>,
                Già esistenti: <strong>{{ count($res['already_exists']) }}</strong>,
                Conflitti sala: <strong>{{ count($res['skipped_conflicts']) }}</strong>
            </div>
        @endif

        @if (!empty($summary))
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-muted small">Candidati a creare</div>
                            <div class="fs-4">{{ $summary['create_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-muted small">Già esistenti</div>
                            <div class="fs-4">{{ $summary['already_exists_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-muted small">Conflitti disponibilità</div>
                            <div class="fs-4">{{ $summary['conflicts_availability'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="text-muted small">Slot già occupati</div>
                            <div class="fs-4">{{ $summary['conflicts_existing'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($preview_by_day))
            @foreach ($preview_by_day as $date => $hoursMap)
                @php
                    $roomNames = collect($rooms)->pluck('label', 'id');
                @endphp
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        {{ \Carbon\Carbon::parse($date)->isoFormat('dddd D MMMM YYYY') }}
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
                                    @foreach ($hours as $h)
                                        <tr>
                                            <th>{{ $h }}</th>

                                            @foreach ($rooms as $room)
                                                @php
                                                    $rid = $room['id'];
                                                    $cell = $hoursMap[$h][$rid] ?? [
                                                        'operators' => [],
                                                        'already_exists' => [],
                                                        'has_existing_lesson' => false,
                                                    ];
                                                @endphp

                                                <td class="text-center">
                                                    @if ($cell['has_existing_lesson'])
                                                        <span class="badge bg-warning text-dark me-1">Occupata</span>
                                                    @endif

                                                    @if (empty($cell['operators']) && empty($cell['already_exists']))
                                                        <span class="text-muted">—</span>
                                                    @else
                                                        {{-- Operatori candidati da creare --}}
                                                        @foreach ($cell['operators'] as $op)
                                                            <span class="badge bg-primary me-1">{{ $op['name'] }}</span>
                                                        @endforeach

                                                        {{-- Lezioni già esistenti (mostra nome se disponibile) --}}
                                                        @foreach ($cell['already_exists'] as $ex)
                                                            @php
                                                                $oid = $ex['operator_id'] ?? null;
                                                                $oname = $operator_names[$oid] ?? '#' . $oid;
                                                            @endphp
                                                            <span class="badge bg-success me-1">{{ $oname }}</span>
                                                        @endforeach
                                                    @endif
                                                </td>
                                            @endforeach

                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
            @endforeach

            <form method="POST" action="{{ route('admin.availability.generate.run') }}" class="text-end">
                @csrf
                <input type="hidden" name="from" value="{{ $from }}">
                <input type="hidden" name="to" value="{{ $to }}">
                <button class="btn btn-primary">Genera lezioni</button>
            </form>
        @endif

        @if (session('result'))
            @php $res = session('result'); @endphp
            <div class="mt-4">
                @if (!empty($res['skipped_conflicts']))
                    <div class="card mb-3">
                        <div class="card-header">Conflitti sala evitati</div>
                        <div class="card-body">
                            <ul class="mb-0">
                                @foreach (array_slice($res['skipped_conflicts'], 0, 20) as $c)
                                    @php $rn = $roomNames[$c['room_id']] ?? ('Sala '.$c['room_id']); @endphp
                                    <li>{{ $c['date'] }} {{ $c['time'] }} — {{ $rn }} (op:
                                        {{ $operator_names[$c['operator_id']] ?? '#' . $c['operator_id'] }})</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
                @if (!empty($res['already_exists']))
                    <div class="card">
                        <div class="card-header">Già esistenti</div>
                        <div class="card-body">
                            <ul class="mb-0">
                                @foreach (array_slice($res['already_exists'], 0, 20) as $a)
                                    <li>{{ $a['date'] }} {{ $a['time'] }} — Sala {{ $a['room_id'] }} (op:
                                        #{{ $a['operator_id'] }})</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
