@extends('layouts.app')

@section('page-title', 'Clienti')

@section('content')
    <div class="container mt-4" style="max-width:1000px;">
        {{-- Titolo --}}
        <div class="mb-2">
            <h1 class="h4 m-0">Clienti</h1>
        </div>

        {{-- Barra strumenti sotto al titolo: cerca + nuovo --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <form method="GET" class="d-flex flex-grow-1" style="gap:.5rem;">
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm"
                    placeholder="Cerca nome, email, telefono">
                <button class="btn btn-sm btn-outline-secondary">Cerca</button>
            </form>
            <a href="{{ route('operator.clients.create') }}" class="btn btn-sm my-btn-brand-primary">
                + Crea cliente
            </a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Cliente</th>
                            <th class="text-center" style="width:1%">Contatti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            @php
                                $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                                $displayName = $fullName !== '' ? $fullName : $u->email ?? 'Utente #' . $u->id;

                                // testuale per corpo messaggio di comodo
                                $bodyTxt = 'Ciao ' . ($u->first_name ?? '') . ',';
                                $mailto = $u->email
                                    ? 'mailto:' .
                                        $u->email .
                                        '?subject=' .
                                        rawurlencode('') .
                                        '&body=' .
                                        rawurlencode($bodyTxt)
                                    : null;

                                $e164 = $u->phone;
                                $waDigits = $e164 ? preg_replace('/\D+/', '', $e164) : null;
                                $waLink = $waDigits
                                    ? 'https://wa.me/' . $waDigits . '?text=' . rawurlencode($bodyTxt)
                                    : null;
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('admin.users.show', $u) }}" class="text-decoration-none">
                                        {{ $displayName }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown d-inline-block" data-bs-display="static">
                                        <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="fa-solid fa-address-card fa-lg"></i>
                                            <span class="visually-hidden">Contatti</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($u?->email)
                                                <li>
                                                    <a class="dropdown-item text-primary" href="mailto:{{ $u->email }}">
                                                        <i class="fa-solid fa-envelope me-2"></i>{{ $u->email }}
                                                    </a>
                                                </li>
                                            @endif

                                            @if ($waLink)
                                                <li>
                                                    <a class="dropdown-item text-whatsapp" href="{{ $waLink }}"
                                                        target="_blank" rel="noopener">
                                                        <i class="fa-brands fa-whatsapp me-2"></i>{{ $u->phone }}
                                                    </a>
                                                </li>
                                            @elseif ($u->phone)
                                                <li>
                                                    <span class="dropdown-item-text">
                                                        <i class="fa-solid fa-phone me-2"></i>{{ $u->phone }}
                                                    </span>
                                                </li>
                                            @endif

                                            @unless ($u?->email || $u?->phone)
                                                <li>
                                                    <span class="dropdown-item-text text-muted">Nessun contatto</span>
                                                </li>
                                            @endunless
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted text-center p-4">Nessun cliente trovato.</td>
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
