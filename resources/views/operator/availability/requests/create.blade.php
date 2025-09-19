@extends('layouts.app')

@section('content')
    <div class="container py-4">

        {{-- Bottone indietro su riga separata --}}
        <div class="mb-2">
            <a href="{{ route('operator.availability.show') }}" class="btn btn-secondary">
                <i class="fa-solid fa-chevron-left me-1"></i> Indietro
            </a>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Proponi modifiche — {{ $operatorName }}</h1>
        </div>

        {{-- Mappa colori per le stanze --}}
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
        @endphp

        {{-- Legenda dinamica in alto (se disponibile) --}}
        @if (!empty($legend))
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                @foreach ($legend as $rid => $info)
                    <span class="badge {{ $roomBadgeClass[$rid] ?? 'bg-secondary' }}">{{ $info['abbr'] }}</span>
                    <span class="me-3">{{ $info['name'] }}</span>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('operator.availability.requests.store') }}" class="card">
            @csrf
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Effettivo da</label>
                        <input type="date" name="effective_from" class="form-control" value="{{ $effective_from }}">
                        <div class="form-text">Tipicamente il prossimo lunedì</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start" style="width: 90px;">Ora</th>
                                @foreach ($days as $d)
                                    <th>{{ $d['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hours as $hour)
                                <tr>
                                    <th class="text-start">{{ $hour }}</th>
                                    @foreach ($days as $d)
                                        @php
                                            $current = $matrix[(int) $d['key']][$hour] ?? null;
                                            $current = is_null($current) ? '' : (string) (int) $current;
                                        @endphp
                                        <td>
                                            <div class="availability-cell position-relative">
                                                <select name="slots[{{ $d['key'] }}][{{ $hour }}]"
                                                    class="form-select form-select-sm availability-select"
                                                    data-original="{{ $current }}">
                                                    <option value="" data-abbr="—" data-badge-class="bg-secondary"
                                                        @selected($current === '')>—</option>
                                                    @foreach ($legend ?? [] as $rid => $info)
                                                        <option value="{{ $rid }}"
                                                            data-abbr="{{ $info['abbr'] }}"
                                                            data-badge-class="{{ $roomBadgeClass[$rid] ?? 'bg-secondary' }}"
                                                            @selected($current === (string) $rid)>
                                                            {{ $info['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                {{-- Badge “lettera stanza” accanto allo chevron del select --}}
                                                <span class="room-abbr-badge badge d-none"></span>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer text-end">
                <button class="btn my-btn-brand-primary">Invia richiesta</button>
            </div>
        </form>
    </div>

    <style>
        .cell-changed {
            background-color: #fff3cd;
        }

        .cell-changed .form-select {
            background-color: #fff3cd !important;
        }

        /* Badge “lettera” posizionato vicino alla freccia del select */
        .availability-cell .room-abbr-badge {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 2.2rem;
            /* subito a sinistra della freccia nativa */
            font-weight: 700;
            font-size: .75rem;
            padding: .2rem .35rem;
        }

        /* più aria su mobile per vedere bene lettera + chevron */
        @media (max-width: 576px) {
            .availability-cell .form-select.form-select-sm {
                min-height: 2.5rem;
                padding-right: 3.25rem;
                /* spazio per freccia + badge */
            }
        }

        @media (min-width: 577px) {
            .availability-cell .form-select.form-select-sm {
                padding-right: 3.25rem;
                /* spazio anche su desktop */
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Badge lettera accanto allo chevron
            document.querySelectorAll('.availability-cell').forEach(function(cell) {
                const sel = cell.querySelector('.availability-select');
                const badge = cell.querySelector('.room-abbr-badge');

                function updateBadge() {
                    const opt = sel.selectedOptions && sel.selectedOptions[0] ? sel.selectedOptions[0] :
                        null;
                    const abbr = opt ? (opt.getAttribute('data-abbr') || '') : '';
                    const cls = opt ? (opt.getAttribute('data-badge-class') || 'bg-secondary') :
                        'bg-secondary';

                    if (abbr && abbr !== '—') {
                        badge.className = 'room-abbr-badge badge ' + cls;
                        badge.textContent = abbr;
                        badge.classList.remove('d-none');
                    } else {
                        badge.className = 'room-abbr-badge badge d-none';
                        badge.textContent = '';
                    }
                }

                sel.addEventListener('change', updateBadge);
                updateBadge(); // init
            });

            // Evidenzia la cella se cambia valore
            document.querySelectorAll('.availability-select').forEach(function(sel) {
                var td = sel.closest('td');
                var orig = sel.dataset.original || '';

                function mark() {
                    var now = sel.value || '';
                    if (now !== orig) td.classList.add('cell-changed');
                    else td.classList.remove('cell-changed');
                }
                sel.addEventListener('change', mark);
                mark();
            });
        });
    </script>
@endsection
