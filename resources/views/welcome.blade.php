@extends('layouts.app')

@section('content')
    <div>
        <div class="hero">
            {{-- Media di background (video file) --}}
            <div class="hero__media">
                <video autoplay muted loop playsinline preload="metadata"
                    poster="{{ Vite::asset('resources/images/hero-poster.jpg') }}">
                    <source src="{{ Vite::asset('resources/videos/hero.mp4') }}" type="video/mp4">
                </video>

            </div>

            {{-- Overlay contenuti (mobile-first) --}}
            <div class="hero__overlay">
                <div class="container min-vh-100 d-flex align-items-center">
                    <div class="w-100 text-center py-5">

                        {{-- Logo --}}
                        <div class="logo-wrapper mx-auto mb-4 mt-4">
                            <img src="{{ Vite::asset('resources/img/logo.png') }}" alt="Centro Pilates – Ada Turco"
                                class="img-fluid logo-hero" />
                        </div>

                        {{-- Pulsanti --}}
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center mb-4">
                            <a href="{{ route('login') }}" class="my-btn-brand-primary btn-lg px-4">Accedi</a>
                            <a href="{{ route('register') }}" class="my-btn-accent-saffron btn-lg px-4">Registrati</a>
                        </div>

                        {{-- Separatore --}}
                        <hr class="my-4 mx-auto hr-narrow border-2 my-border-offwhite" />

                        {{-- Descrizione --}}
                        <p class="my-text-offwhite px-3 mx-auto copy-narrow">
                            Al Pilates Studio ci assicuriamo che tutto quello che facciamo sia fatto con la più alta qualità
                            possibile.
                            Siamo qui per aiutarti a raggiungere i tuoi obiettivi per uno stile di vita basato sul benessere
                            e
                            sulla salute del tuo corpo.
                        </p>
                        <p class="my-text-offwhite px-3 mx-auto copy-narrow">
                            Lavoriamo in modo personalizzato con ogni cliente, e siamo costantemente aggiornati su tutte le
                            novità
                            scientifiche che riguardano la biomeccanica del movimento.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endsection
