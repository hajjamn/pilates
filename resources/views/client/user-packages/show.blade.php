{{-- resources/views/client/user-packages/show.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container client-user-packages mt-4">

        <a href="{{ route('client.user-packages.index') }}" class="btn btn-secondary mb-3"><i
                class="fa-solid fa-chevron-left"></i>
            Torna ai pacchetti</a>

        @php
            $pkg = $userPackage->package;
            $total = (int) ($pkg->total_lessons ?? 0);
            $remaining = (int) $userPackage->lessons_remaining;
            $used = max(0, $total - $remaining);
            $pct = $total > 0 ? round(($used / $total) * 100) : 0;
            $isActive = $remaining > 0;
        @endphp

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">{{ $pkg->name ?? 'Pacchetto' }}</h2>
                        <div class="text-muted small">
                            Acquistato:
                            {{ optional($userPackage->purchased_at)->timezone(config('app.timezone', 'Europe/Rome'))->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <div>
                        <span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                            {{ $isActive ? 'Attivo' : 'Esaurito' }}
                        </span>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <div
                            class="progress progress--thin flex-grow-1 {{ $isActive ? 'progress--accent-saffron' : 'progress--muted' }}">
                            <div class="progress-bar" style="width: {{ $pct }}%;"
                                aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="small text-nowrap">{{ $used }} / {{ $total }}</div>
                    </div>
                    <div class="text-muted small mt-1">Rimasti: {{ $remaining }}</div>
                </div>
            </div>
        </div>

        <h3 class="h6 mb-3">Lezioni scalate da questo pacchetto</h3>

        @if ($usages->isEmpty())
            <div class="alert alert-info mb-0">Nessuna lezione ha ancora scalato questo pacchetto.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="text-muted small">
                        <tr>
                            <th>Data</th>
                            <th>Ora</th>
                            <th>Sala</th>
                            <th>Operatore</th>
                            <th class="text-center">Presenza</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($usages as $usage)
                            @php
                                $lesson = $usage->lesson;
                                $startsAt = optional($lesson?->starts_at)->timezone(
                                    config('app.timezone', 'Europe/Rome'),
                                );
                                $roomName = $lesson?->room?->name;
                                $operatorName = $lesson?->operator?->full_name ?? ($lesson?->operator?->name ?? null);
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $startsAt ? $startsAt->format('d/m/Y') : '—' }}</td>
                                <td class="text-nowrap">{{ $startsAt ? $startsAt->format('H:i') : '—' }}</td>
                                <td>{{ $roomName ?? '—' }}</td>
                                <td>{{ $operatorName ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $usage->attended ? 'bg-primary' : 'bg-warning text-dark' }}">
                                        {{ $usage->attended ? 'Presente' : 'Assente' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if ($lesson)
                                        <a class="btn btn-sm my-btn-accent-saffron"
                                            href="{{ route('client.lessons.show', $lesson->id) }}">
                                            Dettagli
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $usages->links() }}
        @endif
    </div>
@endsection
