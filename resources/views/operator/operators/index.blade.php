@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Operatori</h1>

            @if (auth()->user()->hasRole('admin'))
                <a href="{{ route('operator.operators.create') }}" class="btn btn-primary">
                    + Crea nuovo operatore
                </a>
            @endif
        </div>

        @if ($operators->isEmpty())
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>Nessun operatore presente.</span>
                @if (auth()->user()->hasRole('admin'))
                    <a href="{{ route('operator.operators.create') }}" class="btn btn-sm btn-outline-primary">
                        Crea il primo
                    </a>
                @endif
            </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Ruoli</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($operators as $operator)
                    <tr>
                        <td><a href="{{ route('operator.operators.show', $operator) }}">{{ $operator->full_name }}</a>
                        </td>
                        <td><a href="mailto: {{ $operator->email }}">{{ $operator->email }}</a></td>
                        <td><a href="https://wa.me/{{ $operator->number }}?text= Cara/o {{ $operator->full_name }}, "><i
                                    class="fab fa-whatsapp text-whatsapp"></i>{{ $operator->phone }}</a></td>
                        <td>
                            {{ implode(', ', $operator->getRoleNames()->toArray()) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
