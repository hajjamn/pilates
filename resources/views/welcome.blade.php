@extends('layouts.app')

@section('content')
    <style>
        :root {
            --bs-primary: #798C7B;
            /* verde principale */
            --bs-primary-rgb: 121, 140, 123;
            --bs-secondary: #A4B4A5;
            /* verde secondario */
            --bs-secondary-rgb: 164, 180, 165;
            --bs-light: #F4F4F2;
            /* background */
            --bs-light-rgb: 244, 244, 242;
            --bs-body-bg: #F4F4F2;
        }

        .hero {
            background: linear-gradient(180deg, rgba(121, 140, 123, .15), rgba(121, 140, 123, 0));
            border-radius: 1.25rem;
        }

        .card-soft {
            background: #fff;
            border: 1px solid #C2CDC3;
            border-radius: 1rem;
        }

        .btn-pill {
            border-radius: 50rem;
        }

        .logo {
            height: 100px;
            /* da 64 → 100 */
            max-height: 120px;
            /* limite su schermi grandi */
        }

        @media (min-width: 768px) {
            .logo {
                height: 120px;
            }
        }
    </style>

    <div class="container py-5">
        <div class="hero p-4 p-md-5 text-center">
            <img src="{{ Vite::asset('resources/img/logo.jpeg') }}" alt="Centro Pilates – Ada Turco" class="logo mb-3">
            <h1 class="h3 mb-2">Centro Pilates – Ada Turco</h1>
            <p class="text-muted mb-4">
                Prenota le lezioni, gestisci i tuoi crediti e segui i progressi, tutto dal tuo smartphone.
            </p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg btn-pill px-4">Iscriviti ora</a>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg btn-pill px-4">Accedi</a>
            </div>
        </div>

        <div class="row g-3 mt-4">
            <div class="col-12 col-md-4">
                <div class="card-soft p-3 h-100">
                    <div class="fw-semibold">Calendario settimanale</div>
                    <small class="text-muted">Disponibilità in tempo reale e prenotazione in pochi tap.</small>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card-soft p-3 h-100">
                    <div class="fw-semibold">Promemoria</div>
                    <small class="text-muted">Ricevi notifiche sulle tue lezioni.</small>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card-soft p-3 h-100">
                    <div class="fw-semibold">Area personale</div>
                    <small class="text-muted">Crediti, storico prenotazioni e dettagli abbonamento.</small>
                </div>
            </div>
        </div>
    </div>
@endsection
