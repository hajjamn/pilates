@extends('layouts.app')
@section('page-title', 'Le mie lezioni')

@section('content')
    <div class="container mt-4" style="max-width: 1000px;">

        {{-- Back + titolo --}}
        <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">
            <i class="fa-solid fa-chevron-left"></i> Indietro
        </a>

        {{-- Barra filtri --}}
        <form method="GET" class="card mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-end">

                <div>
                    <label class="form-label small mb-1">Quando</label>
                    <select name="time" class="form-select form-select-sm">
                        <option value="future" {{ $time === 'future' ? 'selected' : '' }}>Future</option>
                        <option value="past" {{ $time === 'past' ? 'selected' : '' }}>Passate</option>
                        <option value="all" {{ $time === 'all' ? 'selected' : '' }}>Tutte</option>
                    </select>
                </div>

                <div>
                    <label class="form-label small mb-1">Stato prenotazione</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="booked" {{ $status === 'booked' ? 'selected' : '' }}>Prenotate</option>
                        <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>Cancellate</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tutte</option>
                    </select>
                </div>

                <div>
                    <label class="form-label small mb-1">Sala</label>
                    <select name="room_id" class="form-select form-select-sm">
                        <option value="">Tutte</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}"
                                {{ (string) $roomId === (string) $room->id ? 'selected' : '' }}>
                                {{ $room->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label small mb-1">Operatore</label>
                    <select name="operator_id" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        @foreach ($operators as $op)
                            @php
                                $label = $op->full_name ?? trim(($op->first_name ?? '') . ' ' . ($op->last_name ?? ''));
                                if ($label === '') {
                                    $label = $op->email;
                                }
                            @endphp
                            <option value="{{ $op->id }}"
                                {{ (string) $operatorId === (string) $op->id ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ms-auto">
                    <button class="btn my-btn-brand-primary btn-sm">Applica</button>
                    <a class="btn btn-danger btn-sm" href="{{ route('client.lessons.index') }}">Azzera</a>
                </div>
            </div>
        </form>

        {{-- Risultati --}}
        @if ($lessons->count() === 0)
            <div class="alert alert-light border text-muted">Nessuna lezione trovata.</div>
        @else
            <div class="row row-cols-1 row-cols-md-2 g-3">
                @foreach ($lessons as $lesson)
                    <div class="col">
                        <x-calendar.lesson-card-client :lesson="$lesson" />
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $lessons->links() }}
            </div>
        @endif
    </div>
@endsection
