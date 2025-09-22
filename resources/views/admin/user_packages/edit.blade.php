{{-- resources/views/admin/user_packages/edit.blade.php --}}
@extends('layouts.app')

@section('page-title', 'Modifica pacchetto utente')

@section('content')
    <div class="container mt-4" style="max-width:600px;">
        <div class="card">
            <div class="card-header fw-semibold">Modifica lezioni rimanenti</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.user-packages.update', ['userPackage' => $userPackage->id]) }}"
                    class="row g-3">
                    @csrf
                    @method('PATCH')
                    <div class="col-12 col-md-6">
                        <label class="form-label small">Lezioni rimanenti</label>
                        <input type="number" min="0" max="9999" name="lessons_remaining"
                            value="{{ old('lessons_remaining', (int) $userPackage->lessons_remaining) }}"
                            class="form-control">
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <a href="{{ route('admin.users.show', $userPackage->user_id) }}"
                            class="btn btn-outline-secondary">Annulla</a>
                        <button class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
