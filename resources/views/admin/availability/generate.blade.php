@extends('layouts.app')

@section('page-title', 'Genera lezioni dalle disponibilità')

@section('content')
    <div class="container py-3" style="max-width:1000px;">

        {{-- Header + back --}}
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h1 class="h4 mb-0">Genera lezioni dalle disponibilità</h1>
            <a href="{{ route('admin.availability.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-chevron-left"></i> Indietro
            </a>
        </div>

        {{-- Toolbar (mobile-first): Anteprima range + Genera in alto --}}
        <div class="card mb-3">
            <div class="card-body">
                <form id="previewForm" method="GET" action="{{ route('admin.availability.generate.form') }}"
                    class="row g-2 g-sm-3 align-items-end">
                    <div class="col-12 col-sm-4 col-md-3">
                        <label class="form-label small">Da</label>
                        <input type="date" name="from" class="form-control form-control-sm"
                            value="{{ $from }}">
                    </div>
                    <div class="col-12 col-sm-4 col-md-3">
                        <label class="form-label small">A</label>
                        <input type="date" name="to" class="form-control form-control-sm"
                            value="{{ $to }}">
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <button class="btn btn-sm btn-primary w-100">Anteprima</button>
                    </div>
                    <div class="col-6 col-sm-12 col-md-3 text-sm-end">
                        {{-- Genera (in alto) --}}
                        <button id="generateTopBtn" type="button" class="btn btn-sm my-btn-brand-primary w-100">
                            <i class="fa-solid fa-bolt"></i> Genera lezioni
                        </button>
                    </div>
                </form>

                {{-- Hidden form per la POST di generazione (usato da bottone in alto) --}}
                <form id="generateHiddenForm" method="POST" action="{{ route('admin.availability.generate.run') }}"
                    class="d-none">
                    @csrf
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                </form>
            </div>
        </div>

        {{-- Esito ultima generazione --}}
        @if (session('result'))
            @php $res = session('result'); @endphp
            <div class="alert alert-success">
                Creati: <strong>{{ count($res['created_ids']) }}</strong>,
                Già esistenti: <strong>{{ count($res['already_exists']) }}</strong>,
                Conflitti sala: <strong>{{ count($res['skipped_conflicts']) }}</strong>
            </div>
        @endif

        {{-- KPI riepilogo anteprima --}}
        @if (!empty($summary))
            <div class="row g-2 g-sm-3 mb-3">
                {{-- Candidati a creare --}}
                <div class="col-6 col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-3">
                            <div class="text-muted small">Candidati a creare</div>
                            <div class="fs-5 fs-sm-4">{{ $summary['create_count'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                {{-- Slot già presenti (già esistenti + già occupati) --}}
                <div class="col-6 col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-3">
                            <div class="text-muted small">Slot già presenti</div>
                            <div class="fs-5 fs-sm-4">
                                <span class="badge bg-exists">
                                    {{ ($summary['already_exists_count'] ?? 0) + ($summary['conflicts_existing'] ?? 0) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conflitti disponibilità --}}
                <div class="col-6 col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-3">
                            <div class="text-muted small">Conflitti disponibilità</div>
                            <div class="fs-5 fs-sm-4">
                                <span class="badge bg-conflict">{{ $summary['conflicts_availability'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @endif

        {{-- Tabella anteprima per giorno --}}
        @if (!empty($preview_by_day))
            @php
                $roomNames = collect($rooms)->pluck('label', 'id');
            @endphp

            @foreach ($preview_by_day as $date => $hoursMap)
                <div class="card mb-3 mb-sm-4">
                    <div class="card-header fw-semibold">
                        {{ \Carbon\Carbon::parse($date)->isoFormat('dddd D MMMM YYYY') }}
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:90px;">Ora</th>
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

                                                    // stati
                                                    $numCandidates = count($cell['operators'] ?? []);
                                                    $hasExisting = !empty($cell['has_existing_lesson']);

                                                    // classi visuali (priorità: conflitto > esistente > to-plan)
                                                    $cellClasses = [];
                                                    if ($numCandidates > 1) {
                                                        $cellClasses[] = 'cell-conflict';
                                                    } elseif ($hasExisting) {
                                                        $cellClasses[] = 'cell-exists';
                                                    } elseif ($numCandidates === 1) {
                                                        $cellClasses[] = 'cell-to-plan';
                                                    }

                                                    // nomi da mostrare (sempre "flat", niente badge)
                                                    $names = [];

                                                    // operatori candidati da creare
                                                    foreach ($cell['operators'] as $op) {
                                                        $names[] = $op['name'];
                                                    }

                                                    // operatori già esistenti (exact match) -> usa dizionario $operator_names
                                                    foreach ($cell['already_exists'] as $ex) {
                                                        $oid = $ex['operator_id'] ?? null;
                                                        $oname = $operator_names[$oid] ?? ($oid ? '#' . $oid : null);
                                                        if ($oname) {
                                                            $names[] = $oname;
                                                        }
                                                    }

                                                    // deduplica
                                                    $names = collect($names)->filter()->unique()->values()->all();
                                                @endphp

                                                <td class="text-center {{ implode(' ', $cellClasses) }}">
                                                    @if (!empty($names))
                                                        @foreach ($names as $nm)
                                                            <span class="d-inline-block me-2">{{ $nm }}</span>
                                                        @endforeach
                                                    @else
                                                        {{-- se esiste una lezione ma non abbiamo nomi in preview, lasciamo solo la cella colorata --}}
                                                        <span class="text-muted">—</span>
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

            {{-- CTA Genera anche in fondo (come prima) --}}
            <form method="POST" action="{{ route('admin.availability.generate.run') }}" class="text-end">
                @csrf
                <input type="hidden" name="from" value="{{ $from }}">
                <input type="hidden" name="to" value="{{ $to }}">
                <button class="btn my-btn-brand-primary">
                    <i class="fa-solid fa-bolt"></i> Genera lezioni
                </button>
            </form>
        @endif

        {{-- Dettaglio esito (facoltativo, come prima) --}}
        @if (session('result'))
            @php $res = session('result'); @endphp
            <div class="mt-3">
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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const previewForm = document.getElementById('previewForm');
                const genHidden = document.getElementById('generateHiddenForm');
                const genTopBtn = document.getElementById('generateTopBtn');

                if (genTopBtn && previewForm && genHidden) {
                    genTopBtn.addEventListener('click', () => {
                        const from = previewForm.querySelector('input[name="from"]')?.value || '';
                        const to = previewForm.querySelector('input[name="to"]')?.value || '';
                        genHidden.querySelector('input[name="from"]').value = from;
                        genHidden.querySelector('input[name="to"]').value = to;
                        genHidden.submit();
                    });
                }
            });
        </script>
    @endpush
@endsection
