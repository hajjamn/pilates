@extends('layouts.app')
@section('page-title', 'Dettaglio operatore')

@section('content')
    <div class="container" style="max-width:1100px;">
        <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">← Indietro</a>

        <h1 class="h5 mb-3">Operatore: {{ $operator->full_name }}</h1>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informazioni principali</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Nome:</strong> {{ $operator->full_name }}</li>
                    <li class="list-group-item">
                        <strong>Email:</strong>
                        <a class="text-decoration-none" href="mailto:{{ $operator->email }}">{{ $operator->email }}</a>
                    </li>
                    <li class="list-group-item">
                        <strong>Telefono:</strong>
                        @php
                            $digits = $operator->phone ? preg_replace('/\D+/', '', $operator->phone) : null;
                            if ($digits && str_starts_with($digits, '0')) {
                                $digits = '39' . $digits;
                            }
                            $waText = rawurlencode('Ciao ' . $operator->full_name . '!');
                        @endphp
                        @if ($digits)
                            <a class="text-decoration-none"
                                href="https://wa.me/{{ $digits }}?text={{ $waText }}" target="_blank"
                                rel="noopener">
                                <i class="fab fa-whatsapp me-1"></i>{{ $operator->phone }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </li>
                    <li class="list-group-item"><strong>Data di nascita:</strong>
                        {{ $operator->birth_date ? $operator->birth_date->format('d/m/Y') : 'N/A' }}</li>
                    <li class="list-group-item">
                        <strong>Email verificata:</strong>
                        {{ $operator->email_verified_at ? $operator->email_verified_at->format('d/m/Y H:i') : '❌ Non verificata' }}
                    </li>
                    <li class="list-group-item"><strong>Ruoli:</strong>
                        {{ implode(', ', $operator->getRoleNames()->toArray()) }}</li>
                </ul>
            </div>
        </div>

        {{-- Riepilogo lezioni operate --}}
        <div class="card my-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Lezioni operate</h5>

                <div class="row g-3">
                    @php
                        $sections = [
                            ['key' => ['future', 'active'], 'title' => 'Prossime • Attive'],
                            ['key' => ['future', 'canceled'], 'title' => 'Prossime • Annullate'],
                            ['key' => ['past', 'active'], 'title' => 'Passate • Svolte'],
                            ['key' => ['past', 'canceled'], 'title' => 'Passate • Annullate'],
                        ];
                    @endphp

                    @foreach ($sections as $s)
                        @php [$when, $status] = $s['key']; @endphp
                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>{{ $s['title'] }}</span>
                                    <span class="badge bg-secondary">{{ count($lessons[$when][$status] ?? []) }}</span>
                                </div>

                                <ul class="list-group list-group-flush">
                                    @forelse (($lessons[$when][$status] ?? []) as $lesson)
                                        @php
                                            $isCanceled = (bool) $lesson->canceled;
                                            $isPast = $lesson->starts_at->isPast();
                                        @endphp
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ $lesson->starts_at->translatedFormat('ddd D MMMM') }}
                                                        · {{ $lesson->starts_at->format('H:i') }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        @if ($lesson->room)
                                                            {{ $lesson->room->name }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    @if ($isCanceled)
                                                        <span class="badge text-bg-danger">Cancellata</span>
                                                    @elseif($isPast)
                                                        <span class="badge text-bg-secondary">Conclusa</span>
                                                    @else
                                                        <span class="badge text-bg-success">Attiva</span>
                                                    @endif
                                                    <div class="mt-2">
                                                        <a href="{{ route('lessons.show', $lesson) }}"
                                                            class="btn btn-outline-secondary btn-sm">
                                                            Dettagli lezione
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted fst-italic">Nessuna lezione.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
@endsection
