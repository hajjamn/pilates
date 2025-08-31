@props([
    'lesson',
    'mode' => 'operator', // 'operator' | 'admin'
])

@php
    $isCanceled = (bool) $lesson->canceled;
    $isPast = $lesson->starts_at?->isPast();
    $bookings = $lesson->lessonUsers ?? collect();
    $clientsCount = $bookings->count();
    $max = $lesson->max_clients ?? null;

    $operatorName = $lesson->operator?->full_name
        ?? trim(($lesson->operator?->first_name ?? '').' '.($lesson->operator?->last_name ?? ''));

    $roomName = $lesson->room?->name ?? '—';

    $statusLabel = $isCanceled ? 'Annullata' : ($isPast ? 'Conclusa' : 'Attiva');
    $statusStyle = $isCanceled ? ['#fee2e2','#991b1b'] : ($isPast ? ['#e5e7eb','#374151'] : ['#dcfce7','#166534']);
@endphp

<div class="card shadow-sm" style="border:1px solid #e5e7eb;border-radius:12px;overflow:visible;" data-lesson-id="{{ $lesson->id }}">
    <div class="d-flex align-items-center justify-content-between" style="padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;">
        <div class="d-flex flex-column">
            <div class="d-flex align-items-center gap-2">
                <div class="fw-bold" style="font-size:1.05rem;">
                    {{ $lesson->starts_at?->translatedFormat('H:i — d MMM') }}
                </div>
                <span class="badge" style="background:{{ $statusStyle[0] }}; color:{{ $statusStyle[1] }}; font-weight:600;">
                    {{ $statusLabel }}
                </span>
            </div>
            <div class="text-muted small">
                Sala: <strong>{{ $roomName }}</strong>
                @if($mode === 'admin')
                    · Operatore: <strong>{{ $operatorName ?: '—' }}</strong>
                @endif
            </div>
        </div>

        <div class="text-end">
            <div class="small text-muted">Iscritti</div>
            <div class="fw-semibold">
                {{ $clientsCount }}@if(!is_null($max)) / {{ $max }} @endif
                @if(!is_null($max) && $clientsCount > $max)
                    <span class="badge text-bg-warning">over</span>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
    {{-- ... titolo+badge stato ... --}}
    @if($mode === 'admin' || (auth()->user()?->hasRole('operatore') && (int)auth()->id() === (int)$lesson->operator_id))
        @if(!$isCanceled)
            <form method="POST" action="{{ route('lessons.cancel', $lesson) }}" class="d-inline">
                @csrf
                <button class="btn btn-outline-danger btn-sm">Annulla</button>
            </form>
        @else
            <form method="POST" action="{{ route('lessons.uncancel', $lesson) }}" class="d-inline">
                @csrf
                <button class="btn btn-success btn-sm">Ripristina</button>
            </form>
        @endif
    @endif
</div>

    </div>

    {{-- Lista iscritti --}}
    <div class="p-3">
        @if($bookings->isEmpty())
            <div class="alert alert-light border text-muted m-0">Nessun iscritto.</div>
        @else
            <div class="table-responsive">
                <table class="table align-middle m-0">
                    <thead>
                        <tr class="small text-muted">
                            <th>Cliente</th>
                            <th>Contatti</th>
                            <th>Pacchetto</th>
                            <th>Paid</th>
                            <th>Attended</th>
                            <th style="width:1%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $b)
                            @php
                                $u = $b->user;
                                $fullName = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->email ?? 'Utente #'.$u->id);
                                $pkg = $b->userPackage;
                                $pkgLabel = $pkg?->package?->name ? ($pkg->package->name.' (rimasti: '.$pkg->lessons_remaining.')') : null;
                            @endphp
                            <tr data-booking-id="{{ $b->id }}">
                                <td class="fw-semibold">
                                    @if($mode === 'admin')
                                        <a href="{{ route('admin.users.show', $u) }}" class="text-decoration-none">
                                            {{ $fullName }}
                                        </a>
                                    @else
                                        {{ $fullName }}
                                    @endif
                                </td>
                                <td class="small">
                                    @if($isCanceled)
                                        {{-- Per lezioni annullate mostriamo subito contatti --}}
                                        <div>{{ $u->phone ?? '—' }}</div>
                                        <div><a href="mailto:{{ $u->email }}">{{ $u->email }}</a></div>
                                    @else
                                        <span class="text-muted">Email:</span> <a href="mailto:{{ $u->email }}">{{ $u->email }}</a>
                                        @if(!empty($u->phone)) · <span class="text-muted">Tel:</span> {{ $u->phone }} @endif
                                    @endif
                                </td>
                                <td class="small">
                                    @if($pkgLabel)
                                        <span class="badge text-bg-light">{{ $pkgLabel }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm toggle-paid"
                                        data-url="{{ route('bookings.togglePaid', $b) }}"
                                        @if($isCanceled) disabled @endif
                                    >
                                        {{ $b->paid ? 'Paid' : 'Unpaid' }}
                                    </button>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm toggle-attended"
                                        data-url="{{ route('bookings.toggleAttended', $b) }}"
                                        @if($isCanceled) disabled @endif
                                    >
                                        {{ $b->attended ? '✓ Attended' : '—' }}
                                    </button>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('bookings.cancel', $b) }}" class="d-inline remove-booking-form">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm">Rimuovi</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Aggiungi cliente --}}
    <div class="p-3 border-top">
        @if($isCanceled)
            <div class="alert alert-warning mb-0">Lezione annullata: non è possibile aggiungere iscritti.</div>
        @else
            <form class="add-booking-form" data-action="{{ route('bookings.store', $lesson) }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label small">Cerca cliente</label>
                        <input type="text" class="form-control form-control-sm client-search" placeholder="Nome, email o telefono">
                        <input type="hidden" name="user_id" class="client-id">
                        <div class="list-group position-absolute shadow-sm w-100 d-none client-results" style="z-index:20; max-height:240px; overflow:auto;"></div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small">Pacchetto (opz.)</label>
                        <select name="user_package_id" class="form-select form-select-sm package-select" disabled>
                            <option value="">— Nessuno —</option>
                        </select>
                        <div class="form-check mt-1">
                            <input class="form-check-input use-package" type="checkbox" value="1" id="usePackage-{{ $lesson->id }}">
                            <label class="form-check-label small" for="usePackage-{{ $lesson->id }}">Usa da pacchetto</label>
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <div class="form-check mt-4">
                            <input class="form-check-input mark-paid" type="checkbox" value="1" id="markPaid-{{ $lesson->id }}">
                            <label class="form-check-label small" for="markPaid-{{ $lesson->id }}">Segna pagato</label>
                        </div>
                    </div>

                    <div class="col-6 col-md-2 text-end">
                        <button class="btn btn-primary btn-sm w-100 add-booking-btn">Aggiungi</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function(){
                const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                function fetchJSON(url, opts={}) {
                    const base = {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    };
                    return fetch(url, Object.assign(base, opts)).then(async r=>{
                        if(!r.ok){
                            let data; try{ data = await r.json(); }catch(e){}
                            const err = new Error('Request failed');
                            err.response = data || {};
                            err.status = r.status;
                            throw err;
                        }
                        return r.json();
                    });
                }

                // Delegation per TUTTE le card
                document.addEventListener('click', async (e)=>{
                    const t = e.target;

                    // Toggle Paid
                    if(t.closest('.toggle-paid')){
                        e.preventDefault();
                        const btn = t.closest('.toggle-paid');
                        const url = btn.dataset.url;
                        btn.disabled = true;
                        try{
                            const res = await fetchJSON(url, { method:'POST' });
                            const paid = !!res?.booking?.paid;
                            btn.textContent = paid ? 'Paid' : 'Unpaid';
                        }catch(err){
                            alert(err.response?.message || 'Errore nel toggle paid');
                        }finally{
                            btn.disabled = false;
                        }
                    }

                    // Toggle Attended
                    if(t.closest('.toggle-attended')){
                        e.preventDefault();
                        const btn = t.closest('.toggle-attended');
                        const url = btn.dataset.url;
                        btn.disabled = true;
                        try{
                            const res = await fetchJSON(url, { method:'POST' });
                            const attended = !!res?.booking?.attended;
                            btn.textContent = attended ? '✓ Attended' : '—';
                        }catch(err){
                            alert(err.response?.message || 'Errore nel toggle attended');
                        }finally{
                            btn.disabled = false;
                        }
                    }
                });

                // Rimozione (submit normale va bene, ma intercetto per UX senza reload)
                document.addEventListener('submit', async (e)=>{
                    const form = e.target.closest('.remove-booking-form');
                    if(!form) return;
                    e.preventDefault();
                    if(!confirm('Rimuovere questa prenotazione?')) return;
                    const row = form.closest('tr');
                    try{
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
                            body: new URLSearchParams(new FormData(form)),
                        });
                        if(res.ok){
                            row?.remove();
                        }else{
                            alert('Errore nella rimozione');
                        }
                    }catch(_){
                        alert('Errore di rete');
                    }
                });

                // Autocomplete + add booking
                document.querySelectorAll('.add-booking-form').forEach(form=>{
                    const action = form.dataset.action;
                    const searchInput = form.querySelector('.client-search');
                    const results = form.querySelector('.client-results');
                    const userIdInput = form.querySelector('.client-id');
                    const packageSelect = form.querySelector('.package-select');
                    const usePackage = form.querySelector('.use-package');
                    const markPaid = form.querySelector('.mark-paid');
                    const addBtn = form.querySelector('.add-booking-btn');

                    // autocomplete
                    let lastQuery = '';
                    let controller = null;

                    function closeResults(){ results.classList.add('d-none'); results.innerHTML = ''; }

                    searchInput?.addEventListener('input', async ()=>{
                        const q = searchInput.value.trim();
                        userIdInput.value = '';
                        packageSelect.innerHTML = '<option value="">— Nessuno —</option>';
                        packageSelect.disabled = true;

                        if(q.length < 2){ closeResults(); return; }

                        lastQuery = q;
                        if(controller) controller.abort();
                        controller = new AbortController();

                        try{
                            const res = await fetchJSON("{{ route('clients.search') }}?q="+encodeURIComponent(q), { signal: controller.signal });
                            const list = res?.data || [];
                            if(q !== lastQuery) return;

                            results.innerHTML = '';
                            list.forEach(item=>{
                                const a = document.createElement('button');
                                a.type = 'button';
                                a.className = 'list-group-item list-group-item-action';
                                a.innerHTML = `<strong>${item.name || '—'}</strong><br><small>${item.email || ''} ${item.phone ? ' · '+item.phone : ''}</small>`;
                                a.addEventListener('click', ()=>{
                                    searchInput.value = item.name || item.email || ('Utente #'+item.id);
                                    userIdInput.value = item.id;
                                    // pacchetti
                                    const pkgs = item.packages || [];
                                    packageSelect.innerHTML = '<option value="">— Nessuno —</option>';
                                    pkgs.forEach(p=>{
                                        const opt = document.createElement('option');
                                        opt.value = p.id;
                                        opt.textContent = p.label;
                                        packageSelect.appendChild(opt);
                                    });
                                    packageSelect.disabled = pkgs.length === 0;
                                    closeResults();
                                });
                                results.appendChild(a);
                            });

                            results.classList.toggle('d-none', list.length === 0);
                        }catch(_){
                            // ignore
                        }
                    });

                    document.addEventListener('click', (ev)=>{
                        if(!results.contains(ev.target) && ev.target !== searchInput){
                            closeResults();
                        }
                    });

                    // abilita/disabilita select pacchetti al check
                    usePackage?.addEventListener('change', ()=>{
                        if(usePackage.checked){
                            packageSelect.disabled = false;
                        }else{
                            packageSelect.value = '';
                            packageSelect.disabled = true;
                        }
                    });

                    // submit add
                    form.addEventListener('submit', async (e)=>{
                        e.preventDefault();
                        if(!userIdInput.value){
                            alert('Seleziona un cliente dall’elenco.');
                            return;
                        }

                        addBtn.disabled = true;
                        try{
                            const payload = new URLSearchParams();
                            payload.set('user_id', userIdInput.value);
                            if(markPaid.checked) payload.set('paid', '1');
                            if(usePackage.checked) {
                                payload.set('use_package', '1');
                                if(packageSelect.value) payload.set('user_package_id', packageSelect.value);
                            }

                            const res = await fetchJSON(action, {
                                method: 'POST',
                                body: payload
                            });

                            // Ricarico la pagina per semplicità (in alternativa potremmo aggiornare la riga dinamicamente)
                            window.location.reload();
                        }catch(err){
                            const errors = err.response?.errors;
                            const first = errors ? Object.values(errors)[0][0] : (err.response?.message || 'Errore');
                            alert(first);
                        }finally{
                            addBtn.disabled = false;
                        }
                    });
                });
            })();
        </script>
    @endpush
@endonce
