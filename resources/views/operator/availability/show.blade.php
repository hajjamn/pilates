@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Disponibilità settimanali — {{ $operatorName }}</h1>
            <div class="small text-muted">0 = Lunedì … 6 = Domenica · Orari 09:00–21:00</div>
        </div>

        @if (!$hasAny)
            <div class="alert alert-warning">Nessuna disponibilità impostata.</div>
        @endif

        <a href="{{ route('operator.availability.requests.create') }}" class="btn btn-primary mb-3">Proponi Cambio</a>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start" style="width: 80px;">Ora</th>
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
                                    $val = $matrix[(string) $d['key']][$hour] ?? null;
                                @endphp
                                <td>
                                    @if ($val === 1)
                                        <span class="badge bg-primary">A</span>
                                    @elseif($val === 2)
                                        <span class="badge bg-secondary">B</span>
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

        <div class="mt-3">
            <span class="badge bg-primary">A</span> {{ $legend[1] ?? 'Sala A' }}
            <span class="ms-3 badge bg-secondary">B</span> {{ $legend[2] ?? 'Sala B' }}
        </div>
    </div>
@endsection
