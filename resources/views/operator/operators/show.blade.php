@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Dettaglio operatore: {{ $operator->full_name }}</h1>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informazioni principali</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Nome:</strong> {{ $operator->full_name }}</li>
                    <li class="list-group-item"><strong>Email:</strong> <a class="text-decoration-none"
                            href="mailto:{{ $operator->email }}">{{ $operator->email }}</a></li>
                    <li class="list-group-item"><strong>Telefono:</strong> <a class="text-decoration-none"
                            href="https://wa.me/{{ $operator->number }}?text= Cara/o {{ $operator->full_name }}, "><i
                                class="fab fa-whatsapp text-whatsapp"></i>
                            {{ $operator->phone ?? 'N/A' }}</a>
                    </li>
                    <li class="list-group-item"><strong>Data di nascita:</strong>
                        {{ $operator->birth_date ? $operator->birth_date->format('d/m/Y') : 'N/A' }}</li>
                    <li class="list-group-item"><strong>Email verificata:</strong>
                        {{ $operator->email_verified_at ? $operator->email_verified_at->format('d/m/Y H:i') : '❌ Non verificata' }}
                    </li>
                    <li class="list-group-item"><strong>Ruoli:</strong>
                        {{ implode(', ', $operator->getRoleNames()->toArray()) }}</li>
                </ul>
            </div>
        </div>

        @if (auth()->user()->hasRole('admin'))
            <a href="{{ route('operator.operators.index') }}" class="btn btn-secondary">Torna alla lista</a>
        @endif
        <a href="{{ route('operator.operators.edit', $operator) }}" class="btn btn-primary">Modifica</a>

        {{-- Riepilogo --}}
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
                                    <span class="badge bg-secondary">
                                        {{ count($lessons[$when][$status] ?? []) }}
                                    </span>
                                </div>

                                <ul class="list-group list-group-flush">
                                    @forelse (($lessons[$when][$status] ?? []) as $lesson)
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    {{-- Sostituisci le proprietà in base al tuo modello --}}
                                                    <div class="fw-semibold">
                                                        {{ $lesson->title ?? 'Lezione' }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{-- esempio: orario e stanza --}}
                                                        {{ optional($lesson->start_at)->format('d/m/Y H:i') }}
                                                        @if (!empty($lesson->end_at))
                                                            – {{ optional($lesson->end_at)->format('H:i') }}
                                                        @endif
                                                        @if (isset($lesson->room))
                                                            · Sala: {{ $lesson->room->name }}
                                                        @endif
                                                        @if (isset($lesson->client))
                                                            · Cliente: {{ $lesson->client->full_name }}
                                                        @endif
                                                    </div>
                                                </div>
                                                @if ($status === 'canceled')
                                                    <span class="badge bg-danger">Annullata</span>
                                                @elseif($when === 'future')
                                                    <span class="badge bg-success">In arrivo</span>
                                                @else
                                                    <span class="badge bg-primary">Completata</span>
                                                @endif
                                            </div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted fst-italic">
                                            Nessuna lezione.
                                        </li>
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
