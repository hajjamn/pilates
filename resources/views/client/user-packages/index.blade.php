{{-- resources/views/client/user-packages/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container client-user-packages mt-4">
        <h1 class="h3 mb-4">I miei pacchetti</h1>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($packages->isEmpty())
            <div class="alert alert-info mb-0">Non hai ancora pacchetti acquistati.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-3">
                    <thead class="text-muted small">
                        <tr>
                            <th>Pacchetto</th>
                            <th>Acquistato</th>
                            <th>Stato</th>
                            <th>Progress</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages as $up)
                            @php
                                $total = (int) ($up->package->total_lessons ?? 0);
                                $remaining = (int) $up->lessons_remaining;
                                $used = max(0, $total - $remaining);
                                $pct = $total > 0 ? round(($used / $total) * 100) : 0;
                                $isActive = $remaining > 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $up->package->name ?? 'Pacchetto' }}</div>
                                </td>
                                <td class="text-nowrap">
                                    {{ optional($up->purchased_at)->timezone(config('app.timezone', 'Europe/Rome'))->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $isActive ? 'Attivo' : 'Esaurito' }}
                                    </span>
                                </td>
                                <td class="w-100">
                                    <div class="d-flex align-items-center gap-2">
                                        <div
                                            class="progress progress--thin flex-grow-1 {{ $isActive ? 'progress--accent-saffron' : 'progress--muted' }}">

                                            <div class="progress-bar" style="width: {{ $pct }}%;"
                                                aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="small text-nowrap">{{ $used }} / {{ $total }}</div>
                                    </div>
                                    <div class="text-muted small">Rimasti: {{ $remaining }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('client.user-packages.show', $up) }}"
                                        class="btn btn-sm my-btn-brand-primary">
                                        Dettagli
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $packages->links() }}
        @endif
    </div>
@endsection
