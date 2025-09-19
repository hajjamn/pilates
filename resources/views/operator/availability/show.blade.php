@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h1 class="h4 mb-0">Disponibilità settimanali — {{ $operatorName }}</h1>
        </div>
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
            foreach ($legend as $rid => $info) {
                $roomBadgeClass[$rid] = $badgePalette[$i % count($badgePalette)];
                $i++;
            }
        @endphp

        {{-- 🔹 NEW: legenda in alto, con lettera + nome stanza --}}
        @if (!empty($legend))
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                @foreach ($legend as $rid => $info)
                    <span class="badge {{ $roomBadgeClass[$rid] ?? 'bg-secondary' }}">{{ $info['abbr'] }}</span>
                    <span class="me-3">{{ $info['name'] }}</span>
                @endforeach
            </div>
        @endif

        @if (!$hasAny)
            <div class="alert alert-warning">Nessuna disponibilità impostata.</div>
        @endif

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('operator.availability.requests.create') }}" class="btn my-btn-brand-primary">
                Proponi Cambio
            </a>
            <a href="{{ route('operator.availability.requests.index') }}" class="btn btn-outline-secondary">
                Le mie modifiche
            </a>
        </div>

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
                                    @if ($val && isset($legend[$val]))
                                        {{-- 🔹 CHANGED: mostra iniziale dinamica con colore coerente --}}
                                        <span class="badge {{ $roomBadgeClass[$val] ?? 'bg-secondary' }}">
                                            {{ $legend[$val]['abbr'] }}
                                        </span>
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
@endsection
