{{-- resources/views/dev/css-test.blade.php --}}
@extends('layouts.app', ['navPartial' => 'partials.nav.dev'])

@section('title', 'CSS Test – Brand & Accent Buttons')

@section('content')
    <div class="container py-4">
        <div class="row g-3">
            <div class="col-12">
                <h1 class="h4 mb-1">CSS Test: Bottoni & Card</h1>
            </div>

            {{-- BRAND PRIMARY --}}
            <div class="col-12">
                <h2 class="h6 mb-2">Brand primary (verde)</h2>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn my-btn-brand-primary">Brand</button>
                    <button class="btn my-btn-brand-primary active" aria-pressed="true">Brand Active</button>
                    <button class="btn my-btn-brand-primary" disabled>Brand Disabled</button>

                    <button class="btn my-btn-brand-primary-outline">Brand Outline</button>
                    <button class="btn my-btn-brand-primary-outline active" aria-pressed="true">Outline Active</button>
                    <button class="btn my-btn-brand-primary-outline" disabled>Outline Disabled</button>
                </div>
            </div>

            {{-- ACCENT: SLATE --}}
            <div class="col-12">
                <h2 class="h6 mb-2">Accent: Slate</h2>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn my-btn-accent-slate">Slate</button>
                    <button class="btn my-btn-accent-slate active" aria-pressed="true">Slate Active</button>
                    <button class="btn my-btn-accent-slate" disabled>Slate Disabled</button>

                    <button class="btn my-btn-accent-slate-outline">Slate Outline</button>
                    <button class="btn my-btn-accent-slate-outline active" aria-pressed="true">Outline Active</button>
                    <button class="btn my-btn-accent-slate-outline" disabled>Outline Disabled</button>
                </div>
            </div>

            {{-- ACCENT: INDIGO --}}
            <div class="col-12">
                <h2 class="h6 mb-2">Accent: Indigo</h2>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn my-btn-accent-indigo">Indigo</button>
                    <button class="btn my-btn-accent-indigo active" aria-pressed="true">Indigo Active</button>
                    <button class="btn my-btn-accent-indigo" disabled>Indigo Disabled</button>

                    <button class="btn my-btn-accent-indigo-outline">Indigo Outline</button>
                    <button class="btn my-btn-accent-indigo-outline active" aria-pressed="true">Outline Active</button>
                    <button class="btn my-btn-accent-indigo-outline" disabled>Outline Disabled</button>
                </div>
            </div>

            {{-- ACCENT: SAFFRON --}}
            <div class="col-12">
                <h2 class="h6 mb-2">Accent: Saffron</h2>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn my-btn-accent-saffron">Saffron</button>
                    <button class="btn my-btn-accent-saffron active" aria-pressed="true">Saffron Active</button>
                    <button class="btn my-btn-accent-saffron" disabled>Saffron Disabled</button>

                    <button class="btn my-btn-accent-saffron-outline">Saffron Outline</button>
                    <button class="btn my-btn-accent-saffron-outline active" aria-pressed="true">Outline Active</button>
                    <button class="btn my-btn-accent-saffron-outline" disabled>Outline Disabled</button>
                </div>
            </div>

            {{-- CARD DI TEST --}}
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="h6 card-title mb-2">Card di prova</h3>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn my-btn-brand-primary">CTA Brand</button>
                            <button class="btn my-btn-accent-slate-outline">Secondaria</button>
                            <button class="btn my-btn-accent-indigo">Azione</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="py-3"></div>
            </div>
        </div>
    </div>
@endsection
