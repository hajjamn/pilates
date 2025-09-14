@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Proponi modifiche — {{ $operatorName }}</h1>
            <i>TODO: admin puo' aggiungere modifiche per gli altri?</i>
            <a href="{{ route('operator.availability.show') }}" class="btn btn-outline-secondary">Torna alla vista</a>
        </div>

        {{-- 🔹 NEW: mappa colori per le stanze --}}
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

        {{-- 🔹 NEW: legenda dinamica in alto (se disponibile) --}}
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
                                            <select name="slots[{{ $d['key'] }}][{{ $hour }}]"
                                                class="form-select form-select-sm availability-select"
                                                data-original="{{ $current }}">
                                                <option value="" @selected($current === '')>—</option>

                                                {{-- 🔹 CHANGED: opzioni dinamiche basate su $legend --}}
                                                @foreach ($legend ?? [] as $rid => $info)
                                                    <option value="{{ $rid }}" @selected($current === (string) $rid)>
                                                        {{ $info['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ❌ RIMOSSA: legenda statica in fondo --}}
            </div>

            <div class="card-footer text-end">
                <button class="btn btn-primary">Invia richiesta</button>
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
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
