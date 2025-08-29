@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Disponibilità settimanali — panoramica</h1>
            <a href="{{ route('admin.availability.generate.form') }}" class="btn btn-outline-primary">
                Genera lezioni
            </a>
        </div>

        @foreach ($days as $d)
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    {{ $d['label'] }}
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">Ora</th>
                                    <th class="text-center">Sala A</th>
                                    <th class="text-center">Sala B</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hours as $hour)
                                    <tr>
                                        <th>{{ $hour }}</th>

                                        @foreach ([1, 2] as $roomId)
                                            @php
                                                $ops = $matrix[$d['key']][$hour][$roomId] ?? [];
                                            @endphp
                                            <td class="text-center">
                                                @if (empty($ops))
                                                    <span class="text-muted">—</span>
                                                @else
                                                    @foreach ($ops as $op)
                                                        <a href="{{ route('operator.operators.show', $op['id']) }}"
                                                            class="d-inline-block me-2">
                                                            {{ $op['name'] }}
                                                        </a>
                                                    @endforeach
                                                @endif
                                            </td>
                                        @endforeach

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
