@extends('layouts.app')

@section('page-title', 'Cliente')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">
        {{-- @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="m-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 m-0">Scheda cliente</h1>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Indietro</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 small">
                    <div class="col-12 col-md-4">
                        <div class="text-muted">Nome</div>
                        <div class="fw-semibold">
                            {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '—' }}</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="text-muted">Email</div>
                        <div class="fw-semibold">
                            @if ($user->email)
                                <a class="text-decoration-none text-primary" href="mailto:{{ $user->email }}">
                                    <i class="fa-solid fa-envelope me-1"></i>{{ $user->email }}
                                </a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="text-muted">Telefono</div>
                        <div class="fw-semibold">
                            @php
                                $digits = $user->phone ? preg_replace('/\D+/', '', $user->phone) : null;
                                if ($digits && str_starts_with($digits, '0')) {
                                    $digits = '39' . $digits; // normalizza prefisso Italia
                                }
                                $waText = rawurlencode('Ciao ' . ($user->first_name ?? ($user->full_name ?? '')));
                            @endphp

                            @if ($digits)
                                <a class="text-decoration-none text-whatsapp"
                                    href="https://wa.me/{{ $digits }}?text={{ $waText }}" target="_blank"
                                    rel="noopener">
                                    <i class="fa-brands fa-whatsapp me-1"></i>{{ $user->phone }}
                                </a>
                            @elseif ($user->phone)
                                <span>{{ $user->phone }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Aggiungi pacchetto --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold">Aggiungi pacchetto</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.packages.store', $user) }}"
                    class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-5">
                        <label class="form-label small">Pacchetto</label>
                        <select name="package_id" class="form-select form-select-sm" required>
                            @foreach (\App\Models\Package::orderBy('name')->get(['id', 'name', 'total_lessons']) as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->total_lessons }} lezioni)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Lezioni rimanenti (opz.)</label>
                        <input type="number" min="1" name="lessons_remaining" class="form-control form-control-sm"
                            placeholder="Default da pacchetto">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Data acquisto (opz.)</label>
                        <input type="datetime-local" name="purchased_at" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-1 text-end">
                        <button class="btn my-btn-brand-primary btn-sm w-100">Aggiungi</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pacchetti ATTIVI --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold">Pacchetti attivi</div>
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Pacchetto</th>
                            <th class="text-center">Rimasti</th>
                            <th class="text-nowrap">Acquistato il</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packagesActive as $up)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $up->package?->name ?? '—' }}
                                    @if (!is_null($up->package?->total_lessons))
                                        <span class="text-muted small">({{ $up->package->total_lessons }} lez.)</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge text-bg-success">{{ (int) $up->lessons_remaining }}</span>
                                </td>
                                <td class="text-nowrap">
                                    {{ optional($up->purchased_at)->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary"
                                        href="{{ route('admin.user-packages.edit', $up) }}">Modifica</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center p-4">Nessun pacchetto attivo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pacchetti UTILIZZATI (accordion) --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
                <strong>Pacchetti utilizzati</strong>
            </div>
            <div class="card-body p-0">
                @php
                    $usedCount = $packagesUsed->count();
                    $collapseId = 'packages-used-collapse';
                    $headingId = 'packages-used-head';
                    $isOpen = false; // lascia chiuso di default
                @endphp

                <div class="accordion" id="client-packages-accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="{{ $headingId }}">
                            <button class="accordion-button @unless ($isOpen) collapsed @endunless"
                                type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                <span class="me-2">Storico (utilizzati/esauriti)</span>
                                <span class="badge bg-secondary">{{ $usedCount }}</span>
                            </button>
                        </h2>

                        <div id="{{ $collapseId }}"
                            class="accordion-collapse collapse @if ($isOpen) show @endif"
                            aria-labelledby="{{ $headingId }}" data-bs-parent="#client-packages-accordion">
                            <div class="accordion-body p-0">
                                @if ($usedCount === 0)
                                    <div class="p-3 text-muted fst-italic">Nessun pacchetto utilizzato.</div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead class="small text-muted">
                                                <tr>
                                                    <th>Pacchetto</th>
                                                    <th class="text-center">Rimasti</th>
                                                    <th class="text-nowrap">Acquistato il</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($packagesUsed as $up)
                                                    <tr>
                                                        <td>
                                                            {{ $up->package?->name ?? '—' }}
                                                            @if (!is_null($up->package?->total_lessons))
                                                                <span
                                                                    class="text-muted small">({{ $up->package->total_lessons }}
                                                                    lez.)</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span
                                                                class="badge text-bg-secondary">{{ (int) $up->lessons_remaining }}</span>
                                                        </td>
                                                        <td class="text-nowrap">
                                                            {{ optional($up->purchased_at)->format('d/m/Y H:i') ?? '—' }}
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
                </div>

            </div>
        </div>

        {{-- Lezioni del cliente --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-light">
                <strong>Lezioni del cliente</strong>
            </div>
            <div class="card-body">

                @php
                    $sections = [
                        ['key' => ['future', 'active'], 'title' => 'Prossime - Attive', 'open' => true],
                        ['key' => ['future', 'canceled'], 'title' => 'Prossime - Annullate', 'open' => false],
                        ['key' => ['past', 'active'], 'title' => 'Passate - Svolte', 'open' => false],
                        ['key' => ['past', 'canceled'], 'title' => 'Passate - Annullate', 'open' => false],
                    ];
                @endphp

                <div class="accordion" id="client-lessons-accordion">
                    @foreach ($sections as $idx => $s)
                        @php
                            [$when, $status] = $s['key'];
                            $list = $lessons[$when][$status] ?? collect();
                            $count = is_countable($list) ? $list->count() : 0;
                            $collapseId = "cl-{$when}-{$status}";
                            $headingId = "cl-h-{$when}-{$status}";
                            $isOpen = $s['open'] && $count > 0;
                        @endphp

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="{{ $headingId }}">
                                <button class="accordion-button @unless ($isOpen) collapsed @endunless"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                    aria-controls="{{ $collapseId }}">
                                    <span class="me-2">{{ $s['title'] }}</span>
                                    <span class="badge bg-secondary">{{ $count }}</span>
                                </button>
                            </h2>

                            <div id="{{ $collapseId }}"
                                class="accordion-collapse collapse @if ($isOpen) show @endif"
                                aria-labelledby="{{ $headingId }}" data-bs-parent="#client-lessons-accordion">
                                <div class="accordion-body p-0">
                                    @if ($count === 0)
                                        <div class="p-3 text-muted fst-italic">Nessuna lezione.</div>
                                    @else
                                        <ul class="list-group list-group-flush">
                                            @foreach ($list as $lesson)
                                                @php
                                                    $isCanceled = (bool) $lesson->canceled;
                                                    $isPast = $lesson->starts_at?->isPast();
                                                    $badgeClass = $isCanceled
                                                        ? 'text-bg-danger'
                                                        : ($isPast
                                                            ? 'text-bg-secondary'
                                                            : 'text-bg-success');
                                                    $roomName = $lesson->room?->name;
                                                @endphp
                                                <li class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                                        <div>
                                                            <div class="fw-semibold">
                                                                {{ ucfirst($lesson->starts_at->translatedFormat('l d')) }}
                                                                {{ ucfirst($lesson->starts_at->translatedFormat('F')) }}
                                                                - {{ $lesson->starts_at->format('H:i') }}
                                                            </div>
                                                            <div class="small text-muted">
                                                                @if ($roomName)
                                                                    Sala: <strong>{{ $roomName }}</strong>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge {{ $badgeClass }}">
                                                                @if ($isCanceled)
                                                                    Cancellata
                                                                @elseif ($isPast)
                                                                    Conclusa
                                                                @else
                                                                    Attiva
                                                                @endif
                                                            </span>
                                                            <div class="mt-2">
                                                                <a href="{{ route('lessons.show', $lesson) }}"
                                                                    class="btn btn-sm my-btn-brand-primary">
                                                                    Dettagli
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

    </div>
@endsection
