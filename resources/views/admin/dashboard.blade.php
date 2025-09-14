@extends('layouts.app')
@section('page-title', 'Homepage')

@section('content')
    <div class="container">

        <h2 class="h4">Calendario Prenotazioni</h2>
        <div class="d-grid gap-2 my-3" style="max-width:520px;">
            <a class="btn btn-primary" href="{{ route('calendar.lessons.index') }}">Questa settimana ▸</a>
        </div>
    </div>
@endsection
