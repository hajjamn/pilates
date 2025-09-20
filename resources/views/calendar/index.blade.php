@extends('layouts.app')

@section('page-title', 'Calendario Lezioni')

@section('content')
    <div class="container container-narrow lc-page py-2">

        {{-- SELEZIONATORE MESE --}}
        <div class="d-flex justify-content-center my-2">
            <button id="monthTrigger" type="button" aria-label="Cambia mese" class="btn month-pill fw-semibold">
                {{ $monthLabel }}
            </button>

            {{-- input month nascosto --}}
            <input id="monthInput" type="month" value="{{ $monthIso }}" class="visually-hidden">
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
                    url.searchParams.set('month', ym);
                    url.searchParams.set('day', dayStr);
                    url.searchParams.set('week', weekStr);

                    @if (!empty($roomId))
                        url.searchParams.set('room_id', "{{ $roomId }}");
                    @endif

                    window.location.href = url.toString();
                });
            })();
        </script>

        {{-- SETTIMANA (prep server-side) --}}
        @php
            use Carbon\Carbon;

            $sel = Carbon::parse($selectedDay);
            $weekStartC = Carbon::parse($weekStart);
            $prevW = $weekStartC->copy()->subDays(7);
            $nextW = $weekStartC->copy()->addDays(7);

            $weekUniformMonth = function (Carbon $ws) {
                $days = collect()->range(0, 6)->map(fn($i) => $ws->copy()->addDays($i));
                return $days->map->format('Y-m')->unique();
            };

            $prevMonths = $weekUniformMonth($prevW);
            $nextMonths = $weekUniformMonth($nextW);

            $prevMonthIso = $prevMonths->count() === 1 ? $prevMonths->first() : $monthIso;
            $nextMonthIso = $nextMonths->count() === 1 ? $nextMonths->first() : $monthIso;

            $common = array_filter(
                [
                    'day' => $selectedDay,
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

        {{-- WEEKBAR --}}
        <div class="weekbar-wrapper d-flex align-items-center justify-content-center gap-2 mb-3">
            <a href="#" id="weekPrev" aria-label="Settimana precedente"
                class="btn btn-outline-secondary week-nav-btn">‹</a>

            <div class="weekbar-viewport">
                <div id="weekbarTrack" class="weekbar-track">
                    @foreach ($weekDays as $day)
                        @php $isSelected = $day->toDateString() === $selectedDay; @endphp
                        <a href="{{ route('calendar.lessons.index', [
                            'day' => $day->toDateString(),
                            'week' => $weekStart,
                            'month' => $monthIso,
                            'room_id' => $roomId,
                        ]) }}"
                            class="text-decoration-none">
                            <div class="day-pill {{ $isSelected ? 'is-selected' : '' }}">
                                <div class="day-dow">{{ strtoupper($day->translatedFormat('D')) }}</div>
                                <div class="day-num">{{ $day->format('j') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="#" id="weekNext" aria-label="Settimana successiva"
                class="btn btn-outline-secondary week-nav-btn">›</a>
        </div>

        {{-- Animazione slide --}}
        <script>
            (function() {
                const state = {
                    selectedDay: "{{ $selectedDay }}",
                    weekStart: "{{ $weekStart }}",
                    monthIso: "{{ $monthIso }}",
                    roomId: "{{ $roomId ?? '' }}",
                    route: "{{ route('calendar.lessons.index') }}",
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
                    const jsDow = d.getDay();
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
                    u.searchParams.set('week', state.weekStart);
                    u.searchParams.set('month', state.monthIso);
                    if (state.roomId) u.searchParams.set('room_id', state.roomId);
                    return u.toString();
                }

                function dayButtonHTML(dayDate) {
                    const selected = (iso(dayDate) === state.selectedDay);
                    const wd = fmtWD.format(dayDate).toUpperCase();
                    return `
                  <a href="${buildDayUrl(dayDate)}" class="text-decoration-none">
                    <div class="day-pill ${selected ? 'is-selected' : ''}">
                      <div class="day-dow">${wd}</div>
                      <div class="day-num">${dayDate.getDate()}</div>
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
                        state.monthIso = uniform;
                        if (monthTrigger) monthTrigger.textContent =
                            (fmtMonth.format(new Date(uniform + '-01'))).replace(/^./, c => c.toUpperCase());
                        if (monthInput) monthInput.value = state.monthIso;
                    }
                }

                function renderWeekBar() {
                    const days = getWeekDays(state.weekStart);
                    track.innerHTML = days.map(dayButtonHTML).join('');
                }

                function slideWeek(dir) {
                    const out = (dir === 1) ? '-60%' : '60%';
                    const inOpp = (dir === 1) ? '60%' : '-60%';

                    track.style.transform = `translateX(${out})`;
                    track.addEventListener('transitionend', () => {
                        const nextWeek = iso(addDays(new Date(state.weekStart), dir * 7));
                        state.weekStart = nextWeek;
                        updateMonthPillIfNeeded();

                        track.classList.add('no-transition');
                        renderWeekBar();
                        track.style.transform = `translateX(${inOpp})`;

                        // reflow
                        void track.offsetWidth;
                        track.classList.remove('no-transition');
                        track.style.transform = 'translateX(0)';
                    }, {
                        once: true
                    });
                }

                prev?.addEventListener('click', (e) => {
                    e.preventDefault();
                    slideWeek(-1);
                });
                next?.addEventListener('click', (e) => {
                    e.preventDefault();
                    slideWeek(1);
                });

                // Se vuoi normalizzare la barra via JS all’avvio:
                // renderWeekBar();
            })();
        </script>

        {{-- FILTRI: Sala + Operatore --}}
        <div class="d-flex flex-wrap align-items-center gap-2 my-2">
            <form id="roomFilterForm" method="GET" action="{{ route('calendar.lessons.index') }}" class="d-inline">
                <input type="hidden" name="day" value="{{ $selectedDay }}">
                <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
                <input type="hidden" name="month" value="{{ $monthIso }}">
                @if (!empty($operatorId))
                    <input type="hidden" name="operator_id" value="{{ $operatorId }}">
                @endif

                <label for="roomFilter" class="fw-semibold me-1">Sala:</label>
                <select id="roomFilter" name="room_id" class="form-select form-select-sm d-inline-block w-auto minw-220">
                    <option value="">Tutte le sale</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}"
                            {{ (string) $room->id === (string) $roomId ? 'selected' : '' }}>
                            {{ $room->name }}
                        </option>
                    @endforeach
                </select>
            </form>

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
                    <select id="operatorFilter" name="operator_id"
                        class="form-select form-select-sm d-inline-block w-auto minw-220">
                        <option value="">Tutti gli operatori</option>
                        @foreach ($operators as $op)
                            @php
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

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 my-2">
            <div class="opacity-75 small">
                Giorno selezionato:
                <strong>{{ Carbon::parse($selectedDay)->translatedFormat('l d/m/Y') }}</strong>
            </div>

            <div class="opacity-75 small">
                Totale lezioni trovate: <strong>{{ $lessons->count() }}</strong>
            </div>

            <div class="ms-auto">
                @if ($mode === 'admin')
                    <a href="{{ route('lessons.create') }}" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-plus"></i> Crea lezione
                    </a>
                @elseif($mode === 'operator')
                    <a href="{{ route('lessons.createLite') }}" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-plus"></i> Crea lezione (lite)
                    </a>
                @endif
            </div>
        </div>

        {{-- LISTA LEZIONI --}}
        @if ($mode === 'client')
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

        @if (in_array($mode, ['admin', 'operator']))
            <div class="card my-3">
                <div class="card-header">
                    <strong>Creazione rapida</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('lessons.store') }}" class="row g-2 align-items-end">
                        @csrf

                        <div class="col-12 col-md-3">
                            <label class="form-label small">Sala</label>
                            <select id="qcCreateRoom" name="room_id" class="form-select form-select-sm" required>
                                @foreach ($rooms as $room)
                                    @php
                                        // Usa il campo che avete sul modello Room:
                                        // prova in quest’ordine: default_max_clients, capacity, altrimenti fallback 7
                                        $roomDefault = $room->default_max_clients ?? ($room->capacity ?? 7);
                                        $selected = (string) old('room_id', $roomId ?? '') === (string) $room->id;
                                    @endphp
                                    <option value="{{ $room->id }}" data-default-max="{{ $roomDefault }}"
                                        {{ $selected ? 'selected' : '' }}>
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($mode === 'admin')
                            <div class="col-12 col-md-3">
                                <label class="form-label small">Operatore</label>
                                <select name="operator_id" class="form-select form-select-sm" required>
                                    @foreach ($operators as $op)
                                        @php
                                            $label =
                                                method_exists($op, 'getFullNameAttribute') || isset($op->full_name)
                                                    ? $op->full_name ?? ''
                                                    : trim(($op->first_name ?? '') . ' ' . ($op->last_name ?? ''));
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
                            <input id="qcMaxClients" type="number" name="max_clients" min="1" max="200"
                                class="form-control form-control-sm" required>
                        </div>

                        <div class="col-6 col-md-1 text-end">
                            <button class="btn my-btn-brand-primary btn-sm w-100">Crea</button>
                        </div>
                    </form>

                    @once
                        @push('scripts')
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    // === Default capienza in base alla sala (come prima) ===
                                    const roomSel = document.getElementById('qcCreateRoom');
                                    const maxInp = document.getElementById('qcMaxClients');
                                    if (roomSel && maxInp) {
                                        const getDefaultFromSelected = () => {
                                            const opt = roomSel.selectedOptions && roomSel.selectedOptions[0] ?
                                                roomSel.selectedOptions[0] :
                                                roomSel.options[roomSel.selectedIndex];
                                            const defStr = opt ? opt.getAttribute('data-default-max') : null;
                                            const defNum = defStr ? parseInt(defStr, 10) : NaN;
                                            return Number.isFinite(defNum) ? defNum : null;
                                        };
                                        const applyDefault = () => {
                                            const def = getDefaultFromSelected();
                                            if (def !== null) {
                                                maxInp.value = def;
                                                maxInp.setAttribute('value', def);
                                            }
                                        };
                                        applyDefault();
                                        roomSel.addEventListener('change', applyDefault);
                                    }

                                    // === NEW: pre-seleziona SOLO il giorno sul campo datetime-local ===
                                    const selectedDay = @json($selectedDay); // "YYYY-MM-DD" dal server
                                    const startsInput = document.querySelector('input[name="starts_at"]');
                                    if (startsInput && !startsInput.value && selectedDay) {
                                        // Imposta la data al giorno selezionato, con ora 00:00 (utente poi cambia l’orario)
                                        // NB: datetime-local richiede anche l’ora; 00:00 è il modo più neutro per “non scegliere un’ora”.
                                        startsInput.value = `${selectedDay}T00:00`;
                                    }
                                });
                            </script>
                        @endpush
                    @endonce


                </div>
            </div>
        @endif
        @once
            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        // Auto-submit filtro SALA
                        const roomSel = document.getElementById('roomFilter');
                        const roomForm = document.getElementById('roomFilterForm');
                        if (roomSel && roomForm) {
                            roomSel.addEventListener('change', () => {
                                roomForm.requestSubmit ? roomForm.requestSubmit() : roomForm.submit();
                            });
                        }

                        // Auto-submit filtro OPERATORE (solo se presente nel markup)
                        const opSel = document.getElementById('operatorFilter');
                        const opForm = document.getElementById('operatorFilterForm');
                        if (opSel && opForm) {
                            opSel.addEventListener('change', () => {
                                opForm.requestSubmit ? opForm.requestSubmit() : opForm.submit();
                            });
                        }
                    });
                </script>
            @endpush
        @endonce
    </div>
@endsection
