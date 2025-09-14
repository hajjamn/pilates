@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h1 class="h4 mb-3">Crea cliente</h1>

        <form method="POST" action="{{ route('operator.clients.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome</label>
                    <input name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cognome</label>
                    <input name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input name="email" type="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefono (opz.)</label>
                    <input name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+39 333 1234567">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data di nascita (opz.)</label>
                    <input name="birth_date" type="date" class="form-control" value="{{ old('birth_date') }}">
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-primary">Crea cliente</button>
                <a href="{{ route('operator.dashboard') }}" class="btn btn-link">Annulla</a>
            </div>
        </form>
    </div>
@endsection
