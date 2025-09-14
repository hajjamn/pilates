@extends('layouts.app')

@section('page-title', 'Cliente')

@section('content')
    <div class="container" style="max-width:1000px;">
        {{-- @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="m-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 m-0">Scheda cliente</h1>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Indietro</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="text-muted small">Nome</div>
                        <div class="fw-semibold">
                            {{ trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '—' }}
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="text-muted small">Email</div>
                        <div><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="text-muted small">Telefono</div>
                        <div>{{ $user->phone ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Aggiungi pacchetto --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold">Aggiungi pacchetto</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.packages.store', $user) }}"
                    class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-5">
                        <label class="form-label small">Pacchetto</label>
                        <select name="package_id" class="form-select form-select-sm" required>
                            @foreach (\App\Models\Package::orderBy('name')->get(['id', 'name', 'total_lessons']) as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->total_lessons }} lezioni)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Lezioni rimanenti (opz.)</label>
                        <input type="number" min="1" name="lessons_remaining" class="form-control form-control-sm"
                            placeholder="Default da pacchetto">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Data acquisto (opz.)</label>
                        <input type="datetime-local" name="purchased_at" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-1 text-end">
                        <button class="btn btn-primary btn-sm w-100">Aggiungi</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pacchetti del cliente --}}
        <div class="card">
            <div class="card-header fw-semibold">Pacchetti</div>
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Pacchetto</th>
                            <th>Rimasti</th>
                            <th>Acquistato il</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($packages as $up)
                            <tr>
                                <td>{{ $up->package?->name ?? '—' }}</td>
                                <td>{{ $up->lessons_remaining }}</td>
                                <td>{{ optional($up->purchased_at)->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted text-center p-4">Nessun pacchetto.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
