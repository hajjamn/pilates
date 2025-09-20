@extends('layouts.app')

@section('content')
    <div class="container py-3 py-md-4">

        {{-- Header + Filtri (mobile-first, no sticky, no overflow) --}}
        <div class="mb-3">
            <h1 class="h4 mb-2">Punteggio</h1>

            <form method="GET" action="{{ route('admin.accounting.show') }}" class="w-100">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="from" class="form-label small text-muted mb-1">Data inizio</label>
                        <input id="from" type="date" name="from" class="form-control w-100"
                            value="{{ $from }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="to" class="form-label small text-muted mb-1">Data fine</label>
                        <input id="to" type="date" name="to" class="form-control w-100"
                            value="{{ $to }}">
                    </div>
                    <div class="col-12 col-md-4 d-grid">
                        <button class="btn my-btn-brand-primary py-2">Filtra</button>
                    </div>
                </div>

                {{-- Preset veloci: si avvolgono senza overflow --}}
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @php
                        $tz = config('app.timezone', 'Europe/Rome');
                        $today = \Carbon\Carbon::now($tz)->toDateString();
                        $last7 = \Carbon\Carbon::now($tz)->subDays(6)->toDateString();
                        $last30 = \Carbon\Carbon::now($tz)->subDays(29)->toDateString();
                        $monthStart = \Carbon\Carbon::now($tz)->startOfMonth()->toDateString();
                        $monthEnd = \Carbon\Carbon::now($tz)->endOfMonth()->toDateString();
                    @endphp
                    <a class="btn btn-outline-secondary btn-sm"
                        href="{{ route('admin.accounting.show', ['from' => $today, 'to' => $today]) }}">Oggi</a>
                    <a class="btn btn-outline-secondary btn-sm"
                        href="{{ route('admin.accounting.show', ['from' => $last7, 'to' => $today]) }}">Ultimi 7</a>
                    <a class="btn btn-outline-secondary btn-sm"
                        href="{{ route('admin.accounting.show', ['from' => $last30, 'to' => $today]) }}">Ultimi 30</a>
                    <a class="btn btn-outline-secondary btn-sm"
                        href="{{ route('admin.accounting.show', ['from' => $monthStart, 'to' => $monthEnd]) }}">Questo
                        mese</a>
                </div>
            </form>
        </div>

        {{-- KPI (mobile: 2 col; md: 2x2) --}}
        <div class="row g-2 g-md-3 mb-3">
            <div class="col-6 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Totale lezioni</div>
                        <div class="fs-3 fw-semibold mb-1">Punti {{ number_format($totLessons, 2, ',', '.') }}</div>
                        <div class="text-muted small">Default: Punti
                            {{ number_format($defaultLessonPrice, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Totale pacchetti</div>
                        <div class="fs-3 fw-semibold">Punti {{ number_format($totPackages, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- NEW: Totale giornaliero (lezioni + pacchetti) --}}
            <div class="col-6 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Totale giornaliero</div>
                        <div class="fs-3 fw-semibold">Punti {{ number_format($totDaily, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Punteggi agli operatori</div>
                        <ul class="list-unstyled mb-0 small">
                            @forelse($byOperator as $row)
                                <li class="d-flex justify-content-between gap-2">
                                    <span class="text-truncate">
                                        {{-- link alla pagina operatore --}}
                                        <a href="{{ route('admin.users.show', $row->paid_to_user_id) }}"
                                            class="text-decoration-none">
                                            {{ $row->paidTo?->first_name }} {{ $row->paidTo?->last_name }}
                                        </a>
                                    </span>
                                    <strong>Punti {{ number_format((float) $row->total, 2, ',', '.') }}</strong>
                                </li>
                            @empty
                                <li class="text-muted">—</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>


        {{-- Grafico (mobile height compatta) --}}
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h2 class="h6 mb-0">Incassi giornalieri</h2>
                </div>
                <div class="ratio" style="--bs-aspect-ratio: 50%;">
                    <canvas id="accChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Non pagate: accordion mobile-first --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Lezioni svolte non contate</h2>

                @if ($unpaidLessons->isEmpty())
                    <div class="text-center text-muted">Nessuna lezione da contare nel periodo.</div>
                @else
                    <div class="accordion" id="unpaidAccordion">
                        @foreach ($unpaidLessons as $b)
                            @php
                                $accId = 'unpaid-' . $b->id;
                                $starts = optional($b->lesson?->starts_at)->format('d/m/Y H:i');
                                $client = trim(($b->user?->first_name ?? '') . ' ' . ($b->user?->last_name ?? ''));
                                $operator = trim(
                                    ($b->lesson?->operator?->first_name ?? '') .
                                        ' ' .
                                        ($b->lesson?->operator?->last_name ?? ''),
                                );
                            @endphp

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="h-{{ $accId }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#c-{{ $accId }}" aria-expanded="false"
                                        aria-controls="c-{{ $accId }}">
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold me-2">{{ $starts }}</span>
                                                <span
                                                    class="badge bg-warning-subtle text-warning-emphasis">{{ $client }}</span>
                                            </div>
                                            <div class="small text-muted text-truncate">
                                                Operatore: {{ $operator }}
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="c-{{ $accId }}" class="accordion-collapse collapse"
                                    aria-labelledby="h-{{ $accId }}" data-bs-parent="#unpaidAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6">
                                                <div class="small text-muted mb-1">Cliente</div>
                                                <div>{{ $client }}</div>
                                                <div class="text-muted small">{{ $b->user?->email }}</div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="small text-muted mb-1">Operatore</div>
                                                <div>{{ $operator }}</div>
                                            </div>
                                            <div class="col-6 col-md-3 text-md-end">
                                                <div class="small text-muted mb-1">Importo atteso</div>
                                                <div class="fw-semibold">Punti
                                                    {{ number_format($defaultLessonPrice, 2, ',', '.') }}</div>
                                            </div>
                                        </div>

                                        <hr class="my-3">

                                        <div class="d-flex justify-content-end">
                                            <a href="{{ route('lessons.show', $b->lesson_id) }}"
                                                class="btn my-btn-brand-primary">
                                                Vai al dettaglio lezione
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            const qs = new URLSearchParams({
                from: @json($from),
                to: @json($to)
            });
            const dataUrl = @json(route('admin.accounting.data')) + '?' + qs.toString();

            fetch(dataUrl, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(payload => {
                    if (!payload?.ok) return;

                    const labels = mergeDateKeys(
                        payload.seriesLessons,
                        payload.seriesPackages,
                        ...(Object.values(payload.seriesByOperator || {}).map(o => o.series))
                    );

                    const toSeriesArray = (seriesObj) => labels.map(d => parseFloat(seriesObj?.[d] ?? 0));

                    const datasets = [{
                            label: 'Lezioni (contate)',
                            data: toSeriesArray(payload.seriesLessons)
                        },
                        {
                            label: 'Pacchetti',
                            data: toSeriesArray(payload.seriesPackages)
                        },
                    ];

                    // Aggiunge un dataset per ogni operatore (pagamenti giornalieri a ciascuno)
                    if (payload.seriesByOperator) {
                        Object.values(payload.seriesByOperator).forEach(op => {
                            datasets.push({
                                label: `Operatore: ${op.label}`,
                                data: toSeriesArray(op.series),
                            });
                        });
                    }

                    renderChart(labels, datasets);
                })
                .catch(console.error);

            function mergeDateKeys(...objs) {
                const set = new Set();
                (objs || []).forEach(o => Object.keys(o || {}).forEach(k => set.add(k)));
                return Array.from(set).sort();
            }

            let chart;

            function renderChart(labels, datasets) {
                const ctx = document.getElementById('accChart');
                if (chart) chart.destroy();
                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            x: {
                                ticks: {
                                    maxRotation: 0,
                                    autoSkip: true
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: v => 'Punti ' + v.toLocaleString('it-IT')
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => {
                                        const v = ctx.parsed.y ?? 0;
                                        return `${ctx.dataset.label}: punti ${v.toLocaleString('it-IT', { minimumFractionDigits: 2 })}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })();
    </script>
@endpush
