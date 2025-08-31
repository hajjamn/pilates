@extends('layouts.app') {{-- usa il tuo layout --}}

@section('page-title', 'Calendario Lezioni')

@section('content')
    <div class="container" style="max-width:1100px;">

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

                if (!trigger || !input) return;

                trigger.addEventListener('click', () => {
                    if (input.showPicker) input.showPicker();
                    else input.click();
                });

                input.addEventListener('change', (e) => {
                    const ym = e.target.value; // "YYYY-MM"
                    if (!ym) return;

                    const [Y, M] = ym.split('-').map(Number);
                    const first = new Date(Y, M - 1, 1);
                    const jsDow = first.getDay(); // 0=Dom ... 1=Lun ... 6=Sab
                    const backToMonday = (jsDow + 6) % 7; // giorni per tornare a Lun
                    const monday = new Date(Y, M - 1, 1 - backToMonday);

                    const pad = n => String(n).padStart(2, '0');
                    const dayStr = `${ym}-01`;
                    const weekStr = `${monday.getFullYear()}-${pad(monday.getMonth()+1)}-${pad(monday.getDate())}`;

                    const url = new URL("{{ route('calendar.lessons.index') }}", window.location.origin);
                    url.searchParams.set('month', ym); // mese scelto (pill)
                    url.searchParams.set('day', dayStr); // seleziona il 1° del mese
                    url.searchParams.set('week', weekStr); // mostra la settimana che contiene il 1°

                    @if (!empty($roomId))
                        url.searchParams.set('room_id', "{{ $roomId }}");
                    @endif

                    window.location.href = url.toString();
                });
            })();
        </script>

        {{-- SETTIMANA --}}
        @php
            use Carbon\Carbon;

            $sel = Carbon::parse($selectedDay);
            $weekStartC = Carbon::parse($weekStart);
            $prevW = $weekStartC->copy()->subDays(7);
            $nextW = $weekStartC->copy()->addDays(7);

            // Se la nuova settimana è tutta in un mese → aggiorno month, altrimenti mantengo quello attuale
            $weekUniformMonth = function (Carbon $ws) {
                $days = collect()->range(0, 6)->map(fn($i) => $ws->copy()->addDays($i));
                return $days->map->format('Y-m')->unique();
            };

            $prevMonths = $weekUniformMonth($prevW);
            $nextMonths = $weekUniformMonth($nextW);

            $prevMonthIso = $prevMonths->count() === 1 ? $prevMonths->first() : $monthIso;
            $nextMonthIso = $nextMonths->count() === 1 ? $nextMonths->first() : $monthIso;

            // Costruisco gli URL mantenendo il giorno selezionato e (se c'è) la sala
$common = array_filter(
    [
        'day' => $selectedDay, // NON cambia
        'room_id' => $roomId,
    ],
    fn($v) => $v !== null && $v !== '',
);

$prevUrl = route(
    'calendar.lessons.index',
    array_merge($common, [
        'week' => $prevW->toDateString(),
        'month' => $prevMonthIso,
    ]),
);

$nextUrl = route(
    'calendar.lessons.index',
    array_merge($common, [
        'week' => $nextW->toDateString(),
        'month' => $nextMonthIso,
                ]),
            );
        @endphp

        {{-- WRAPPER con overflow per l’animazione --}}
        <div class="weekbar-wrapper"
            style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:12px;">
            <a href="#" id="weekPrev" aria-label="Settimana precedente" class="btn btn-outline-secondary btn-sm"
                style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">‹</a>

            <div class="weekbar-viewport" style="overflow:hidden;max-width:480px;">
                <div id="weekbarTrack" class="weekbar-track" style="display:flex;gap:4px;transition:transform .28s ease;">
                    {{-- CONTENUTO INIZIALE SERVER-SIDE (opzionale, puoi lasciarlo) --}}
                    @foreach ($weekDays as $day)
                        @php $isSelected = $day->toDateString() === $selectedDay; @endphp
                        <a href="{{ route('calendar.lessons.index', [
                            'day' => $day->toDateString(),
                            'week' => $weekStart,
                            'month' => $monthIso,
                            'room_id' => $roomId,
                        ]) }}"
                            style="text-decoration:none;">
                            <div
                                style="
              padding:6px 10px;border-radius:6px;
              border:1px solid {{ $isSelected ? '#2563eb' : '#d1d5db' }};
              background: {{ $isSelected ? '#e0f2fe' : '#f9fafb' }};
              color: {{ $isSelected ? '#1d4ed8' : '#374151' }};
              text-align:center;min-width:48px;font-weight:600;">
                                <div style="font-size:0.75rem;">{{ strtoupper($day->translatedFormat('D')) }}</div>
                                <div style="font-size:0.9rem;">{{ $day->format('j') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="#" id="weekNext" aria-label="Settimana successiva" class="btn btn-outline-secondary btn-sm"
                style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">›</a>
        </div>


        {{-- Animazione “slide” prima della navigazione --}}
        <script>
            (function() {
                // --- Stato locale ---
                const state = {
                    // YYYY-MM-DD
                    selectedDay: "{{ $selectedDay }}",
                    weekStart: "{{ $weekStart }}",
                    monthIso: "{{ $monthIso }}", // pill del mese attuale
                    roomId: "{{ $roomId ?? '' }}",
                    route: "{{ route('calendar.lessons.index') }}", // per i link dei giorni
                };

                const track = document.getElementById('weekbarTrack');
                const prev = document.getElementById('weekPrev');
                const next = document.getElementById('weekNext');
                const monthTrigger = document.getElementById('monthTrigger');
                const monthInput = document.getElementById('monthInput');

                const fmtMonth = new Intl.DateTimeFormat('it-IT', {
                    month: 'long'
                });
                const fmtWD = new Intl.DateTimeFormat('it-IT', {
                    weekday: 'short'
                });

                const pad = n => String(n).padStart(2, '0');
                const iso = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                const addDays = (date, days) => {
                    const d = new Date(date);
                    d.setDate(d.getDate() + days);
                    return d;
                };
                const mondayOf = (date) => {
                    const d = new Date(date);
                    const jsDow = d.getDay(); // 0=Dom .. 1=Lun .. 6=Sab
                    const back = (jsDow + 6) % 7;
                    return addDays(d, -back);
                };
                const getWeekDays = (weekStartStr) => {
                    const ws = new Date(weekStartStr);
                    return Array.from({
                        length: 7
                    }, (_, i) => addDays(ws, i));
                };

                function buildDayUrl(dayDate) {
                    const u = new URL(state.route, window.location.origin);
                    u.searchParams.set('day', iso(dayDate));
                    u.searchParams.set('week', state.weekStart); // mostro questa settimana
                    u.searchParams.set('month', state.monthIso); // pill coerente
                    if (state.roomId) u.searchParams.set('room_id', state.roomId);
                    return u.toString();
                }

                function dayButtonHTML(dayDate) {
                    const selected = (iso(dayDate) === state.selectedDay);
                    const wd = fmtWD.format(dayDate).toUpperCase(); // LUN, MAR, ...
                    return `
      <a href="${buildDayUrl(dayDate)}" style="text-decoration:none;">
        <div style="
          padding:6px 10px;border-radius:6px;
          border:1px solid ${selected ? '#2563eb' : '#d1d5db'};
          background:${selected ? '#e0f2fe' : '#f9fafb'};
          color:${selected ? '#1d4ed8' : '#374151'};
          text-align:center;min-width:48px;font-weight:600;">
          <div style="font-size:0.75rem;">${wd}</div>
          <div style="font-size:0.9rem;">${dayDate.getDate()}</div>
        </div>
      </a>
    `;
                }

                function uniformMonthOfWeek(weekStartStr) {
                    const months = new Set(getWeekDays(weekStartStr).map(d => `${d.getFullYear()}-${pad(d.getMonth()+1)}`));
                    return (months.size === 1) ? [...months][0] : null;
                }

                function updateMonthPillIfNeeded() {
                    const uniform = uniformMonthOfWeek(state.weekStart);
                    if (uniform) {
                        state.monthIso = uniform; // aggiorno il mese “di vista”
                        if (monthTrigger) monthTrigger.textContent = (fmtMonth.format(new Date(uniform + '-01'))).replace(
                            /^./, c => c.toUpperCase());
                        if (monthInput) monthInput.value = state.monthIso;
                    }
                }

                function renderWeekBar() {
                    const days = getWeekDays(state.weekStart);
                    track.innerHTML = days.map(dayButtonHTML).join('');
                }

                // Animazione: slide “uscita” poi sostituisco contenuto e slide “entrata”
                function slideWeek(dir) {
                    const out = (dir === 1) ? '-60%' : '60%';
                    const inOpp = (dir === 1) ? '60%' : '-60%';

                    // 1) slide-out
                    track.style.transform = `translateX(${out})`;
                    track.addEventListener('transitionend', () => {
                        // 2) aggiorno lo stato (±7 giorni) e month pill se necessario
                        const nextWeek = iso(addDays(new Date(state.weekStart), dir * 7));
                        state.weekStart = nextWeek;
                        updateMonthPillIfNeeded();

                        // 3) sostituisco contenuto e preparo posizione off-screen opposta
                        track.style.transition = 'none';
                        renderWeekBar();
                        track.style.transform = `translateX(${inOpp})`;

                        // 4) forzo reflow e slide-in a 0
                        void track.offsetWidth; // reflow
                        track.style.transition = 'transform .28s ease';
                        track.style.transform = 'translateX(0)';
                    }, {
                        once: true
                    });
                }

                // Prev / Next: niente reload
                prev?.addEventListener('click', (e) => {
                    e.preventDefault();
                    slideWeek(-1);
                });
                next?.addEventListener('click', (e) => {
                    e.preventDefault();
                    slideWeek(1);
                });

                // Se vuoi che all’avvio la barra venga “normalizzata” da JS, scommenta:
                // renderWeekBar();
            })();
        </script>


        {{-- FILTRI: Sala + Operatore --}}
        <div class="d-flex flex-wrap align-items-center gap-2 my-2">

            {{-- Sala (già esistente) --}}
            <form id="roomFilterForm" method="GET" action="{{ route('calendar.lessons.index') }}" class="d-inline">
                <input type="hidden" name="day" value="{{ $selectedDay }}">
                <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
                <input type="hidden" name="month" value="{{ $monthIso }}">
                @if (!empty($operatorId))
                    <input type="hidden" name="operator_id" value="{{ $operatorId }}">
                @endif

                <label for="roomFilter" class="fw-semibold me-1">Sala:</label>
                <select id="roomFilter" name="room_id" class="form-select form-select-sm d-inline-block"
                    style="min-width:220px;">
                    <option value="">Tutte le sale</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}"
                            {{ (string) $room->id === (string) $roomId ? 'selected' : '' }}>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            {{-- Operatore: solo per client/admin --}}
            @if (in_array($mode, ['admin', 'client']))
                <form id="operatorFilterForm" method="GET" action="{{ route('calendar.lessons.index') }}"
                    class="d-inline">
                    <input type="hidden" name="day" value="{{ $selectedDay }}">
                    <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
                    <input type="hidden" name="month" value="{{ $monthIso }}">
                    @if (!empty($roomId))
                        <input type="hidden" name="room_id" value="{{ $roomId }}">
                    @endif

                    <label for="operatorFilter" class="fw-semibold ms-2 me-1">Operatore:</label>
                    <select id="operatorFilter" name="operator_id" class="form-select form-select-sm d-inline-block"
                        style="min-width:220px;">
                        <option value="">Tutti gli operatori</option>
                        @foreach ($operators as $op)
                            @php
                                // se hai accessor full_name sul modello User
                                $label =
                                    method_exists($op, 'getFullNameAttribute') || isset($op->full_name)
                                        ? $op->full_name ?? ''
                                        : trim(($op->first_name ?? '') . ' ' . ($op->last_name ?? ''));
                                if ($label === '') {
                                    $label = $op->email;
                                }
                            @endphp
                            <option value="{{ $op->id }}"
                                {{ (string) $op->id === (string) $operatorId ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                @if ($operatorId)
                    <a href="{{ route(
                        'calendar.lessons.index',
                        array_filter(
                            [
                                'day' => $selectedDay,
                                'week' => $weekStart ?? null,
                                'month' => $monthIso,
                                'room_id' => $roomId,
                            ],
                            fn($v) => $v !== null && $v !== '',
                        ),
                    ) }}"
                        class="ms-1 small text-decoration-underline">
                        Rimuovi filtro operatore
                    </a>
                @endif
            @endif
        </div>

        <script>
            document.getElementById('roomFilter')?.addEventListener('change', () => {
                document.getElementById('roomFilterForm').submit();
            });
            document.getElementById('operatorFilter')?.addEventListener('change', () => {
                document.getElementById('operatorFilterForm').submit();
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

        {{-- CLIENT: card view --}}
        @if ($mode === 'client')
    {{-- invariato --}}
    <div class="row row-cols-1 row-cols-md-2 g-3">
        @forelse($lessons as $lesson)
            <div class="col">
                <x-calendar.lesson-card-client :lesson="$lesson" />
            </div>
        @empty
            <div class="col">
                <div class="alert alert-light border text-muted">Nessuna lezione per il giorno selezionato.</div>
            </div>
        @endforelse
    </div>
@else
    {{-- OPERATOR/ADMIN: card di gestione --}}
    <div class="row row-cols-1 row-cols-md-2 g-3">
        @forelse($lessons as $lesson)
            <div class="col">
                <x-calendar.lesson-card-manage :lesson="$lesson" :mode="$mode" />
            </div>
        @empty
            <div class="col">
                <div class="alert alert-light border text-muted">Nessuna lezione per il giorno selezionato.</div>
            </div>
        @endforelse
    </div>
@endif

        @if(in_array($mode, ['admin','operator']))
<div class="card mb-3">
  <div class="card-body">
    <form method="POST" action="{{ route('lessons.store') }}" class="row g-2 align-items-end">
      @csrf

      <div class="col-12 col-md-3">
        <label class="form-label small">Sala</label>
        <select name="room_id" class="form-select form-select-sm" required>
          @foreach($rooms as $room)
            <option value="{{ $room->id }}">{{ $room->name }}</option>
          @endforeach
        </select>
      </div>

      @if($mode === 'admin')
      <div class="col-12 col-md-3">
        <label class="form-label small">Operatore</label>
        <select name="operator_id" class="form-select form-select-sm" required>
          @foreach($operators as $op)
            @php
              $label = method_exists($op, 'getFullNameAttribute') || isset($op->full_name)
                ? ($op->full_name ?? '')
                : trim(($op->first_name ?? '').' '.($op->last_name ?? ''));
              $label = $label !== '' ? $label : $op->email;
            @endphp
            <option value="{{ $op->id }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      @endif

      <div class="col-12 col-md-3">
        <label class="form-label small">Data/Ora inizio</label>
        <input type="datetime-local" name="starts_at" class="form-control form-control-sm" required>
      </div>

      <div class="col-6 col-md-2">
        <label class="form-label small">Capienza</label>
        <input type="number" name="max_clients" min="1" max="200" class="form-control form-control-sm" required>
      </div>

      <div class="col-6 col-md-1 text-end">
        <button class="btn btn-primary btn-sm w-100">Crea</button>
      </div>
    </form>
  </div>
</div>
@endif

    </div>
@endsection
