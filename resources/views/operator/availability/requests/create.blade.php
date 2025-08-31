@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Proponi modifiche — {{ $operatorName }}</h1>
            <a href="{{ route('operator.availability.show') }}" class="btn btn-outline-secondary">Torna alla vista</a>
        </div>

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
                                                <option value="1" @selected($current === '1')>Sala A</option>
                                                <option value="2" @selected($current === '2')>Sala B</option>
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <span class="badge bg-primary">Sala A</span>
                    <span class="ms-3 badge bg-secondary">Sala B</span>
                </div>
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
