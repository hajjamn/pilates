@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Lista utenti (test)</h1>

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
                @foreach ($operators as $operators)
                    <tr>
                        <td><a href="{{ route('operator.operators.show', $operators) }}">{{ $operators->full_name }}</a>
                        </td>
                        <td><a href="mailto: {{ $operators->email }}">{{ $operators->email }}</a></td>
                        <td><a href="https://wa.me/{{ $operators->number }}?text= Cara/o {{ $operators->full_name }}, "><i
                                    class="fab fa-whatsapp text-whatsapp"></i>{{ $operators->phone }}</a></td>
                        <td>
                            {{ implode(', ', $operators->getRoleNames()->toArray()) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
