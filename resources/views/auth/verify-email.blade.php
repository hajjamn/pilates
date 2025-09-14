@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Verifica il tuo indirizzo email.') }}</div>

                    <div class="card-body">
                        @if (session('status') === 'verification-link-sent')
                            <div class="alert alert-success" role="alert">
                                {{ __('Ti abbiamo inviato un nuovo link di verifica all’indirizzo email fornito.') }}
                            </div>
                        @endif

                        {{ __('Prima di procedere, controlla la tua casella mail per un link di verifica.') }}
                        {{ __('Se non hai ricevuto la mail,') }},
                        <form class="d-inline" method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit"
                                class="btn btn-link p-0 m-0 align-baseline">{{ __('clicca qui per richiederne di nuovo.') }}</button>.
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
