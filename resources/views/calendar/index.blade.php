@extends('layouts.app') {{-- usa il tuo layout --}}

@section('page-title', 'Calendario Lezioni')

@section('content')
    <div class="container" style="max-width:1100px;">
        {{-- Header semplice --}}

        {{-- SELEZIONATORE MESE --}}
        <div style="display:flex;justify-content:center;margin:10px 0 14px;">
            <button id="monthTrigger" type="button" aria-label="Cambia mese"
                style="padding:8px 16px;border-radius:9999px;border:1px solid #cfd8d3;background:#e7efe9;font-weight:600;">
                {{ $monthLabel }}
            </button>

            {{-- input month nascosto --}}
            <input id="monthInput" type="month" value="{{ $monthIso }}"
                style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;">
        </div>

        <script>
            (function() {
                const trigger = document.getElementById('monthTrigger');
                const input = document.getElementById('monthInput');

                trigger.addEventListener('click', () => {
                    if (input.showPicker) input.showPicker();
                    else input.click();
                });

                input.addEventListener('change', (e) => {
                    const ym = e.target.value; // "YYYY-MM"
                    if (!ym) return;

                    // Autoseleziona sempre il primo del mese scelto
                    const dayStr = ym + '-01'; // "YYYY-MM-01"

                    const url = new URL("{{ route('calendar.lessons.index') }}", window.location.origin);
                    url.searchParams.set('month', ym); // mese selezionato
                    url.searchParams.set('day', dayStr); // 1° del mese selezionato

                    @if (!empty($roomId))
                        url.searchParams.set('room_id', "{{ $roomId }}"); // preserva filtro sala
                    @endif

                    window.location.href = url.toString();
                });
            })();
        </script>



        @php
            use Carbon\Carbon;

            $sel = Carbon::parse($selectedDay);
            $prevD = $sel->copy()->subDays(7);
            $nextD = $sel->copy()->addDays(7);

            $common = ['room_id' => $roomId];

            $prevUrl = route(
                'calendar.lessons.index',
                array_filter(
                    [
                        'day' => $prevD->toDateString(),
                        'month' => $prevD->format('Y-m'),
                    ] + $common,
                    fn($v) => $v !== null && $v !== '',
                ),
            );

            $nextUrl = route(
                'calendar.lessons.index',
                array_filter(
                    [
                        'day' => $nextD->toDateString(),
                        'month' => $nextD->format('Y-m'),
                    ] + $common,
                    fn($v) => $v !== null && $v !== '',
                ),
            );
        @endphp

        <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:12px;">
            {{-- Freccia settimana precedente --}}
            <a href="{{ $prevUrl }}" aria-label="Settimana precedente" title="Settimana precedente"
                style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #d1d5db;border-radius:8px;background:#fff;text-decoration:none;">
                ‹
            </a>

            {{-- Qui resta/segue la tua barra dei 7 giorni --}}
            <div style="display:flex;gap:4px;">
                @foreach ($weekDays as $day)
                    @php
                        $isSelected = $day->toDateString() === $selectedDay;
                        $labelDay = strtoupper($day->translatedFormat('D')); // LUN, MAR, ...
                        $url = route(
                            'calendar.lessons.index',
                            array_filter(
                                [
                                    'day' => $day->toDateString(),
                                    'month' => $day->format('Y-m'),
                                    'room_id' => $roomId,
                                ],
                                fn($v) => $v !== null && $v !== '',
                            ),
                        );
                    @endphp

                    <a href="{{ $url }}" style="text-decoration:none;">
                        <div
                            style="
                    padding:6px 10px;border-radius:6px;
                    border:1px solid {{ $isSelected ? '#2563eb' : '#d1d5db' }};
                    background: {{ $isSelected ? '#e0f2fe' : '#f9fafb' }};
                    color: {{ $isSelected ? '#1d4ed8' : '#374151' }};
                    text-align:center;min-width:48px;font-weight:600;">
                            <div style="font-size:0.75rem;">{{ $labelDay }}</div>
                            <div style="font-size:0.9rem;">{{ $day->format('j') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Freccia settimana successiva --}}
            <a href="{{ $nextUrl }}" aria-label="Settimana successiva" title="Settimana successiva"
                style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border:1px solid #d1d5db;border-radius:8px;background:#fff;text-decoration:none;">
                ›
            </a>
        </div>



        {{-- FILTRO SALA --}}
        <div style="display:flex;gap:8px;align-items:center;margin:8px 0 16px;">
            <label for="roomFilter" style="font-weight:600;">Sala:</label>

            <form id="roomFilterForm" method="GET" action="{{ route('calendar.lessons.index') }}">
                {{-- Conserva gli altri parametri --}}
                <input type="hidden" name="day" value="{{ $selectedDay }}">
                <input type="hidden" name="month" value="{{ $monthIso }}">

                <select id="roomFilter" name="room_id"
                    style="padding:8px 10px;border:1px solid #d1d5db;border-radius:10px;min-width:220px;">
                    <option value="">Tutte le sale</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}"
                            {{ (string) $room->id === (string) $roomId ? 'selected' : '' }}>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if ($roomId)
                <a href="{{ route('calendar.lessons.index', ['day' => $selectedDay, 'month' => $monthIso]) }}"
                    style="margin-left:6px;font-size:.9rem;color:#2563eb;text-decoration:underline;">
                    Rimuovi filtro
                </a>
            @endif
        </div>

        <script>
            document.getElementById('roomFilter').addEventListener('change', function() {
                document.getElementById('roomFilterForm').submit();
            });
        </script>

        <div style="display:flex;align-items:center;justify-content:space-between;margin:16px 0 8px;">
            <div style="opacity:.7;font-size:.95rem;">
                <p>Totale lezioni trovate: <strong>{{ $lessons->count() }}</strong></p>
            </div>
            <div style="opacity:.7;font-size:.95rem;">
                Giorno selezionato: <strong>{{ \Carbon\Carbon::parse($selectedDay)->translatedFormat('l d/m/Y') }}</strong>
            </div>
        </div>


        {{-- Tabella lezioni di OGGI --}}
        <div class="card" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
            <div style="padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:600;">
                Lezioni di oggi
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;background:#fcfcfd;">
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Ora</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Sala</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Operatore</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Capienza</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Iscritti</th>
                            <th style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">Stato</th>
                            {{-- eventuale azione (prenota/gestisci) la metteremo dopo --}}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lessons as $lesson)
                            <tr>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;white-space:nowrap;">
                                    {{ $lesson->starts_at?->format('H:i') }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->room?->name ?? '—' }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->operator?->full_name ?? '—' }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->max_clients }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    {{ $lesson->clients_count ?? $lesson->clients->count() }}
                                </td>
                                <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;">
                                    @if ($lesson->canceled)
                                        <span
                                            style="display:inline-block;padding:.2rem .5rem;border-radius:9999px;background:#fee2e2;color:#991b1b;font-weight:600;">
                                            Cancellata
                                        </span>
                                    @elseif($lesson->starts_at->isPast())
                                        <span
                                            style="display:inline-block;padding:.2rem .5rem;border-radius:9999px;background:#e5e7eb;color:#374151;font-weight:600;">
                                            Conclusa
                                        </span>
                                    @else
                                        <span
                                            style="display:inline-block;padding:.2rem .5rem;border-radius:9999px;background:#dcfce7;color:#166534;font-weight:600;">
                                            Attiva
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:16px;color:#6b7280;text-align:center;">
                                    Nessuna lezione per oggi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Nota: qui aggiungeremo in seguito i controlli di navigazione (mese/settimana/giorno/sala) --}}
    </div>
@endsection
