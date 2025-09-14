@extends('layouts.app')

@section('page-title', 'Clienti')

@section('content')
    <div class="container" style="max-width:1000px;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 m-0">Clienti</h1>
            <div class="d-flex" style="gap:.5rem;">
                <form method="GET" class="d-flex" style="gap:.5rem;">
                    <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm"
                        placeholder="Cerca nome, email, telefono">
                    <button class="btn btn-sm btn-outline-secondary">Cerca</button>
                </form>
                <a href="{{ route('operator.clients.create') }}" class="btn btn-sm btn-primary">
                    + Crea cliente
                </a>
            </div>
        </div>


        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefono</th>
                            <th style="width:1%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            @php
                                $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: '—';
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $name }}</td>
                                <td><a href="mailto:{{ $u->email }}">{{ $u->email }}</a></td>
                                <td>{{ $u->phone ?? '—' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" href="{{ route('admin.users.show', $u) }}">Apri</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center p-4">Nessun cliente trovato.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </div>
@endsection
