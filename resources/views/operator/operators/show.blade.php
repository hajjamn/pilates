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

        <a href="{{ route('operator.operators.index') }}" class="btn btn-secondary">Torna alla lista</a>
        <a href="{{ route('operator.operators.edit', $operator) }}" class="btn btn-primary">Modifica</a>
    </div>
@endsection
