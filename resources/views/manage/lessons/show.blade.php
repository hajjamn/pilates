@extends('layouts.app')

@section('page-title', 'Dettaglio lezione')

@section('content')
    <div class="container" style="max-width:900px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">
                Lezione • {{ $lesson->starts_at?->translatedFormat('d MMMM Y, H:mm') }}
            </h1>

            <div class="d-flex gap-2">
                @if ($mode === 'admin')
                    <a href="{{ route('lessons.edit', $lesson) }}" class="btn btn-primary btn-sm">
                        Modifica (admin)
                    </a>
                @else
                    <a href="{{ route('lessons.editLite', $lesson) }}" class="btn btn-primary btn-sm">
                        Modifica (operatore)
                    </a>
                @endif

                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Indietro</a>
            </div>
        </div>

        {{-- Info principali --}}
        @php
            $isCanceled = (bool) $lesson->canceled;
            $statusLabel = $isCanceled ? 'Annullata' : ($lesson->starts_at?->isPast() ? 'Conclusa' : 'Attiva');
            $statusStyle = $isCanceled
                ? ['#fee2e2', '#991b1b']
                : ($lesson->starts_at?->isPast()
                    ? ['#e5e7eb', '#374151']
                    : ['#dcfce7', '#166534']);
            $operatorName =
                $lesson->operator?->full_name ??
                trim(($lesson->operator?->first_name ?? '') . ' ' . ($lesson->operator?->last_name ?? '')) ?:
                $lesson->operator?->email ?? '—';
            $roomName = $lesson->room?->name ?? '—';
        @endphp

        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="small text-muted">Stato</div>
                    <span class="badge"
                        style="background:{{ $statusStyle[0] }}; color:{{ $statusStyle[1] }}; font-weight:600;">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div>
                    <div class="small text-muted">Sala</div>
                    <div class="fw-semibold">{{ $roomName }}</div>
                </div>
                <div>
                    <div class="small text-muted">Operatore</div>
                    <div class="fw-semibold">{{ $operatorName }}</div>
                </div>
                <div>
                    <div class="small text-muted">Capienza</div>
                    <div class="fw-semibold">{{ $lesson->clients_count ?? 0 }} / {{ $lesson->max_clients }}</div>
                </div>
                <div>
                    <div class="small text-muted">Manual override</div>
                    <div class="fw-semibold">{{ $lesson->manual_override ? 'Sì' : 'No' }}</div>
                </div>
            </div>
        </div>

        {{-- Iscritti --}}
        <div class="card">
            <div class="card-header bg-light">
                <strong>Iscritti</strong>
            </div>
            <div class="card-body p-0">
                @php $bookings = $lesson->lessonUsers ?? collect(); @endphp
                @if ($bookings->isEmpty())
                    <div class="p-3 text-muted">Nessun iscritto.</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="small text-muted">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Contatti</th>
                                    <th>Pacchetto</th>
                                    <th>Pagato</th>
                                    <th>Presenza</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bookings as $b)
                                    @php
                                        $u = $b->user;
                                        $fullName =
                                            trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?:
                                            $u->email ?? 'Utente #' . $u->id;

                                        $subject = 'Lezione ' . $lesson->starts_at->format('d/m H:i');
                                        $bodyTxt =
                                            'Ciao ' .
                                            ($u->first_name ?? '') .
                                            ",\nla lezione del " .
                                            $lesson->starts_at->format('d/m H:i') .
                                            '.';
                                        $mailto = $u->email
                                            ? 'mailto:' .
                                                $u->email .
                                                '?subject=' .
                                                rawurlencode($subject) .
                                                '&body=' .
                                                rawurlencode($bodyTxt)
                                            : null;

                                        $e164 = $u->phone;
                                        $waDigits = $e164 ? preg_replace('/\D+/', '', $e164) : null;
                                        $waLink = $waDigits
                                            ? 'https://wa.me/' . $waDigits . '?text=' . rawurlencode($bodyTxt)
                                            : null;

                                        $pkg = $b->userPackage;
                                        $pkgLabel = $pkg?->package?->name
                                            ? $pkg->package->name . ' (rimasti: ' . $pkg->lessons_remaining . ')'
                                            : null;

                                        $paid = (bool) ($b->paid ?? false);
                                        $attended = (bool) ($b->attended ?? false);
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $fullName }}</td>
                                        <td class="small">
                                            @if ($u?->email)
                                                <div>
                                                    <a href="mailto:{{ $u->email }}"> <i
                                                            class="fa-solid fa-envelope me-1"></i> {{ $u->email }}</a>
                                                </div>
                                            @endif
                                            @if ($u->phone)
                                                <div>
                                                    @if ($waLink)
                                                        <a href="{{ $waLink }}" target="_blank" rel="noopener"
                                                            class="text-decoration-none">
                                                            <i class="fa-brands fa-whatsapp me-1"></i>
                                                            {{ $u->phone }}
                                                        </a>
                                                    @else
                                                        <span>{{ $u->phone }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                            @unless ($u?->email || $u?->phone)
                                                <span class="text-muted">—</span>
                                            @endunless
                                        </td>
                                        <td class="small">
                                            @if ($pkgLabel)
                                                <span class="badge text-bg-light">{{ $pkgLabel }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            {{ $paid ? '✓' : '—' }}
                                        </td>

                                        <td class="text-center">
                                            {{ $attended ? '✓' : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
