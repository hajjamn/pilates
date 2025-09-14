@extends('layouts.app')

@section('content')
    <div class="container py-5 text-center">
        <h1 class="mb-4">✨ Benvenuto al Centro Pilates - Ada Turco ✨</h1>

        <p class="lead text-muted mb-5">
            Un luogo dedicato al benessere, al movimento e all’equilibrio.
            Scopri i nostri corsi di Pilates e inizia oggi il tuo percorso.
        </p>

        <div class="mt-4">
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg me-2">
                Iscriviti Ora
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">
                Accedi
            </a>
        </div>

        <div class="mt-5 text-muted small">
            <p>Hai domande? Contattaci e saremo felici di aiutarti.</p>
        </div>
    </div>
@endsection
