@extends('layouts.app')

@section('content')
    <div class="container py-3 py-md-4">

        {{-- Header + Filtri (mobile-first, no sticky, no overflow) --}}
        <div class="mb-3">
            <h1 class="h4 mb-2">Contabilità</h1>

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
                        <button class="btn btn-primary py-2">Filtra</button>
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
                        href="{{ route('admin.accounting.show', ['from' => $monthStart, 'to' => $monthEnd]) }}">Questo mese</a>
                </div>
            </form>
        </div>

        {{-- KPI (mobile: 1 col; md: 2x2) --}}
        <div class="row g-2 g-md-3 mb-3">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Totale lezioni</div>
                        <div class="fs-3 fw-semibold mb-1">€ {{ number_format($totLessons, 2, ',', '.') }}</div>
                        <div class="text-muted small">Prezzo default: €
                            {{ number_format($defaultLessonPrice, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Totale pacchetti</div>
                        <div class="fs-3 fw-semibold">€ {{ number_format($totPackages, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Lezioni non pagate</div>
                        <div class="fs-3 fw-semibold">{{ $unpaidLessons->count() }}</div>
                        <div class="text-muted small">nel periodo</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Pagamenti agli operatori</div>
                        <ul class="list-unstyled mb-0 small">
                            @forelse($byOperator as $row)
                                <li class="d-flex justify-content-between gap-2">
                                    <span class="text-truncate">
                                        {{ $row->paidTo?->first_name }} {{ $row->paidTo?->last_name }}
                                    </span>
                                    <strong>€ {{ number_format((float) $row->total, 2, ',', '.') }}</strong>
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
                    <div class="small text-muted">Lezioni (pagate) vs Pacchetti</div>
                </div>
                <div class="ratio" style="--bs-aspect-ratio: 50%;">
                    <canvas id="accChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Non pagate: cards su mobile, tabella da md+ --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Lezioni svolte non pagate</h2>

                {{-- Cards (solo < md) --}}
                <div class="d-md-none">
                    @forelse ($unpaidLessons as $b)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between">
                                <div class="fw-semibold">{{ optional($b->lesson?->starts_at)->format('d/m/Y H:i') }}</div>
                                <div class="text-muted small">
                                    {{ $b->lesson?->operator?->first_name }} {{ $b->lesson?->operator?->last_name }}
                                </div>
                            </div>
                            <div class="mt-1">
                                <div class="small text-muted">Cliente</div>
                                <div>{{ $b->user?->first_name }} {{ $b->user?->last_name }}</div>
                                <div class="text-muted small">{{ $b->user?->email }}</div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="fw-semibold">€ {{ number_format($defaultLessonPrice, 2, ',', '.') }}</div>
                                <button class="btn btn-success btn-sm js-mark-paid w-auto px-3"
                                    data-booking-id="{{ $b->id }}">
                                    Segna pagata
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted">Nessuna lezione da pagare nel periodo.</div>
                    @endforelse
                </div>

                {{-- Tabella (da md in su) --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Data/Ora</th>
                                <th>Cliente</th>
                                <th>Operatore</th>
                                <th class="text-end">Importo atteso</th>
                                <th class="text-end">Azione</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unpaidLessons as $b)
                                <tr>
                                    <td>{{ optional($b->lesson?->starts_at)->format('d/m/Y H:i') }}</td>
                                    <td>
                                        {{ $b->user?->first_name }} {{ $b->user?->last_name }}<br>
                                        <span class="text-muted small">{{ $b->user?->email }}</span>
                                    </td>
                                    <td>{{ $b->lesson?->operator?->first_name }} {{ $b->lesson?->operator?->last_name }}
                                    </td>
                                    <td class="text-end">€ {{ number_format($defaultLessonPrice, 2, ',', '.') }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-success btn-sm js-mark-paid px-3"
                                            data-booking-id="{{ $b->id }}">
                                            Segna pagata
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nessuna lezione da pagare nel periodo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

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
                    const labels = mergeDateKeys(payload.seriesLessons, payload.seriesPackages);
                    const lessons = labels.map(d => parseFloat(payload.seriesLessons?.[d] ?? 0));
                    const packages = labels.map(d => parseFloat(payload.seriesPackages?.[d] ?? 0));
                    renderChart(labels, lessons, packages);
                })
                .catch(console.error);

            function mergeDateKeys(a, b) {
                const set = new Set([...Object.keys(a || {}), ...Object.keys(b || {})]);
                return Array.from(set).sort();
            }

            let chart;

            function renderChart(labels, lessons, packages) {
                const ctx = document.getElementById('accChart');
                if (chart) chart.destroy();
                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                                label: 'Lezioni (pagate)',
                                data: lessons
                            },
                            {
                                label: 'Pacchetti',
                                data: packages
                            },
                        ]
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
                                    callback: v => '€ ' + v.toLocaleString('it-IT')
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
                                        return `${ctx.dataset.label}: € ${v.toLocaleString('it-IT', { minimumFractionDigits: 2 })}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Azione "Segna pagata" (semplice per ora)
            const ask = (msg) => {
                const val = prompt(msg);
                return (val && val.trim() !== '') ? val.trim() : null;
            };

            document.querySelectorAll('.js-mark-paid').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const bookingId = btn.dataset.bookingId;
                    const paidAt = ask(
                        'Data pagamento (YYYY-MM-DD HH:MM) — lascia vuoto per adesso');
                    const paidToUserId = ask(
                        'ID operatore a cui è stato pagato — lascia vuoto per usare il tuo');

                    const body = new URLSearchParams();
                    if (paidAt) body.append('paid_at', paidAt);
                    if (paidToUserId) body.append('paid_to_user_id', paidToUserId);

                    const res = await fetch(@json(url('/bookings')) + '/' + bookingId +
                        '/toggle-paid', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': @json(csrf_token()),
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: body.toString(),
                        });

                    const json = await res.json();
                    if (json?.ok) location.reload();
                    else alert(json?.message || 'Errore durante il salvataggio');
                });
            });
        })();
    </script>
@endpush
