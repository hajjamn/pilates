@extends('layouts.app')

@section('page-title', 'Disponibilità settimanali — Panoramica')

@section('content')
    <div class="container py-3" style="max-width:1000px;">

        {{-- Titolo --}}
        <div class="mb-2">
            <h1 class="h4 mb-0">Disponibilità settimanali — Panoramica</h1>
        </div>

        {{-- Toolbar sotto al titolo (mobile-first) --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <a href="{{ route('operator.availability.show') }}" class="btn btn-sm btn-outline-secondary">
                Mie disponibilità
            </a>
            <a href="{{ route('admin.availability.requests.index') }}" class="btn btn-sm btn-outline-secondary">
                Richieste cambio
            </a>
            <a href="{{ route('admin.availability.generate.form') }}" class="btn btn-sm my-btn-brand-primary ms-sm-auto">
                Genera lezioni
            </a>
        </div>

        {{-- Navigazione settimana (chevron + input data) --}}
        <form method="GET" action="{{ route('admin.availability.index') }}" class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    {{-- Prev --}}
                    <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        href="{{ route('admin.availability.index', ['week' => $prev_week]) }}">
                        <i class="fa-solid fa-chevron-left"></i>
                        <span class="d-none d-sm-inline ms-1">Settimana precedente</span>
                    </a>

                    {{-- Picker settimana --}}
                    <div class="flex-grow-0">
                        <input type="date" name="week" class="form-control form-control-sm text-center"
                            style="min-width: 150px;" value="{{ $week_start }}">
                    </div>

                    {{-- Next --}}
                    <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        href="{{ route('admin.availability.index', ['week' => $next_week]) }}">
                        <span class="d-none d-sm-inline me-1">Settimana successiva</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

                {{-- Bottone Vai solo da sm in su --}}
                <div class="text-center mt-2 d-none d-sm-block">
                    <button class="btn btn-sm btn-primary">Vai</button>
                </div>
            </div>


            <div class="card-footer small text-muted">
                {{ \Carbon\Carbon::parse($week_start)->isoFormat('D MMM') }} –
                {{ \Carbon\Carbon::parse($week_end)->isoFormat('D MMM YYYY') }}
            </div>
        </form>

        {{-- KPI --}}
        <div class="row g-2 g-sm-3 mb-3">
            <!-- Slot da pianificare -->
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Slot da pianificare</div>
                        <div class="fs-5 fs-sm-4">
                            <span class="badge bg-warning text-dark">{{ $health_counts['to_plan'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lezioni senza disponibilità -->
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Lezioni senza disponibilità</div>
                        <div class="fs-5 fs-sm-4">
                            <span class="badge bg-out-of-availability">
                                {{ $health_counts['out_of_availability'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slot occupati -->
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Slot occupati</div>
                        <div class="fs-5 fs-sm-4">{{ $health_counts['occupied'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <!-- Conflitti disponibilità -->
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small">Conflitti disp.</div>
                        <div class="fs-5 fs-sm-4">
                            <span class="badge bg-danger">{{ $health_counts['conflicts'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        {{-- Griglia per giorni/ore/stanze --}}
        @foreach ($days as $d)
            @php $date = $day_date[$d['key']] ?? null; @endphp
            <div class="card mb-3 mb-sm-4">
                <div class="card-header fw-semibold">
                    {{ $d['label'] }}
                    @if ($date)
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
                                @foreach ($hours as $hour)
                                    <tr>
                                        <th>{{ $hour }}</th>
                                        @foreach ($rooms as $room)
                                            @php
                                                $roomId = $room['id'];

                                                // disponibilità da weekly slots
                                                $ops = $matrix[$d['key']][$hour][$roomId] ?? []; // array di ['id','name']

                                                // conflitto: già calcolato a monte (es. più disponibilità nello stesso slot/sala)
                                                $isConflict = $conflict_map[$d['key']][$hour][$roomId] ?? false;

                                                // lezioni presenti in quello slot
                                                $occArr = $occupied[$date][$hour][$roomId] ?? [];
                                                $isOccupied = !empty($occArr);

                                                // disponibilità presente
                                                $hasAvailability = !empty($ops);

                                                // operatori da mostrare in cella:
                                                // - se c'è disponibilità → mostriamo i nomi dagli slot
// - altrimenti, se c'è lezione fuori disponibilità → mostriamo i nomi dalle lezioni
                                                $namesToShow = [];
                                                if ($hasAvailability) {
                                                    $namesToShow = collect($ops)
                                                        ->pluck('name')
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                } elseif ($isOccupied) {
                                                    $namesToShow = collect($occArr)
                                                        ->pluck('operator_name')
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                }

                                                // classi visuali per i 4 casi
                                                $classes = [];
                                                if ($isConflict) {
                                                    // 4) Conflitto → rosso
                                                    $classes[] = 'cell-conflict';
                                                } elseif ($isOccupied && !$hasAvailability) {
                                                    // 2) Lezione fuori disponibilità → colore dedicato (non rosso)
                                                    $classes[] = 'cell-out-of-availability';
                                                } elseif ($hasAvailability && !$isOccupied) {
                                                    // 3) Da pianificare (disponibilità ma senza lezione) → giallo
                                                    $classes[] = 'cell-to-plan';
                                                }
                                                // 1) Lezione normale (hasAvailability && isOccupied) → nessuna classe: cella "neutra"
                                            @endphp

                                            <td class="text-center {{ implode(' ', $classes) }}">
                                                @if (!empty($namesToShow))
                                                    @foreach ($namesToShow as $nm)
                                                        <span class="d-inline-block me-2">{{ $nm }}</span>
                                                    @endforeach
                                                @else
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

        {{-- Lezioni senza disponibilità (lezioni create ma fuori dallo schema di disponibilità) --}}
        @if (!empty($out_of_availability))
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Lezioni senza disponibilità</strong>
                    <span class="badge bg-out-of-availability">{{ count($out_of_availability) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Ora</th>
                                    <th>Sala</th>
                                    <th>Operatore</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($out_of_availability as $lesson)
                                    <tr class="table-row-link"
                                        onclick="window.location='{{ route('lessons.show', $lesson['lesson_id']) }}'">
                                        <td>{{ \Carbon\Carbon::parse($lesson['date'])->format('d/m/Y') }}</td>
                                        <td>{{ $lesson['time'] }}</td>
                                        <td>{{ $roomNames[$lesson['room_id']] ?? 'Sala ' . $lesson['room_id'] }}</td>
                                        <td>{{ $lesson['operator_name'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('form[action="{{ route('admin.availability.index') }}"]');
                const weekInput = form ? form.querySelector('input[name="week"]') : null;

                if (form && weekInput) {
                    // auto-submit quando l'utente sceglie una data
                    weekInput.addEventListener('change', () => {
                        // normalizza ad YYYY-MM-DD già prodotto dal date input
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                    });
                }
            });
        </script>
    @endpush

@endsection
