@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="fs-4 text-secondary my-4">
            {{ __('Dashboard') }}
        </h2>
        <div class="row justify-content-center">
            <div class="col">
                <div class="card">
                    <div class="card-header">{{ __('User Dashboard') }}</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        {{ __('You are logged in!') }}

                        @if (Auth::user() && !Auth::user()->hasVerifiedEmail())
                            <div class="alert alert-warning mt-2">
                                Il tuo indirizzo email non è verificato. <br>
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary mt-2">
                                        Invia nuovamente email di verifica
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="alert alert-success mt-2">
                                Benvenuto, {{ Auth::user()->getFullNameAttribute() }}. Il tuo indirizzo email è verificato.
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
