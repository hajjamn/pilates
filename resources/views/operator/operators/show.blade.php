@extends('layouts.app')
@section('page-title', 'Dettaglio operatore')

@section('content')
    <div class="container my-4">

        {{-- Header + back --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Operatore: {{ $operator->full_name }}</h1>
            <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">
                <i class="fa-solid fa-chevron-left"></i> Indietro
            </a>
        </div>

        {{-- Card info operatore --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <strong>Informazioni principali</strong>
            </div>
            <div class="card-body">
                @php
                    $digits = $operator->phone ? preg_replace('/\D+/', '', $operator->phone) : null;
                    if ($digits && str_starts_with($digits, '0')) {
                        $digits = '39' . $digits; // normalizza prefisso Italia
                    }
                    $waText = rawurlencode('Ciao ' . $operator->full_name . '!');
                @endphp

                <div class="row g-3 small">
                    <div class="col-12 col-md-6">
                        <div class="text-muted">Nome</div>
                        <div class="fw-semibold">{{ $operator->full_name }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">Email</div>
                        <div class="fw-semibold">
                            <a class="text-decoration-none text-primary" href="mailto:{{ $operator->email }}">
                                <i class="fa-solid fa-envelope me-1"></i>{{ $operator->email }}
                            </a>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">Telefono</div>
                        <div class="fw-semibold">
                            @if ($digits)
                                <a class="text-decoration-none text-whatsapp"
                                    href="https://wa.me/{{ $digits }}?text={{ $waText }}" target="_blank"
                                    rel="noopener">
                                    <i class="fa-brands fa-whatsapp me-1"></i>{{ $operator->phone }}
                                </a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">Data di nascita</div>
                        <div class="fw-semibold">
                            {{ $operator->birth_date ? $operator->birth_date->format('d/m/Y') : 'N/A' }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">Email verificata</div>
                        <div class="fw-semibold">
                            {{ $operator->email_verified_at ? $operator->email_verified_at->format('d/m/Y H:i') : '❌ Non verificata' }}
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted">Ruoli</div>
                        <div class="fw-semibold">{{ implode(', ', $operator->getRoleNames()->toArray()) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riepilogo lezioni operate: accordion con sezioni --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <strong>Lezioni operate</strong>
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

                <div class="accordion" id="operator-lessons-accordion">
                    @foreach ($sections as $idx => $s)
                        @php
                            [$when, $status] = $s['key'];
                            $list = $lessons[$when][$status] ?? [];
                            $count = is_countable($list) ? count($list) : 0;
                            $collapseId = "sect-{$when}-{$status}";
                            $headingId = "heading-{$when}-{$status}";
                            $isOpen = $s['open'] && $count > 0;
                        @endphp

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="{{ $headingId }}">
                                <button class="accordion-button @unless ($isOpen) collapsed @endunless"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                    <span class="me-2">{{ $s['title'] }}</span>
                                    <span class="badge bg-secondary">{{ $count }}</span>
                                </button>
                            </h2>
                            <div id="{{ $collapseId }}"
                                class="accordion-collapse collapse @if ($isOpen) show @endif"
                                aria-labelledby="{{ $headingId }}" data-bs-parent="#operator-lessons-accordion">
                                <div class="accordion-body p-0">
                                    @if ($count === 0)
                                        <div class="p-3 text-muted fst-italic">Nessuna lezione.</div>
                                    @else
                                        <ul class="list-group list-group-flush">
                                            @foreach ($list as $lesson)
                                                @php
                                                    $isCanceled = (bool) $lesson->canceled;
                                                    $isPast = $lesson->starts_at->isPast();
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
                                                                {{ ucfirst($lesson->starts_at->isoFormat('ddd D')) }}
                                                                {{ ucfirst($lesson->starts_at->isoFormat('MMMM')) }}
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
