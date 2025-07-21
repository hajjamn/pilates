@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Lista operatori (test)</h1>

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
