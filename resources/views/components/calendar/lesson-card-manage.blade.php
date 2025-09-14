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
                    {{ $lesson->starts_at?->isoFormat('HH:mm — D MMM') }}
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

            {{-- Pulsante Dettagli --}}
@if($mode === 'admin' || (auth()->user()?->hasRole('operatore') && (int)auth()->id() === (int)$lesson->operator_id))
    <a href="{{ route('lessons.show', $lesson) }}" class="btn btn-outline-primary btn-sm">
        Dettagli
    </a>
@endif

    {{-- ... titolo+badge stato ... --}}
    @if($mode === 'admin' || (auth()->user()?->hasRole('operatore') && (int)auth()->id() === (int)$lesson->operator_id))
        @if(!$isCanceled)
            <form method="POST" action="{{ route('lessons.cancel', $lesson) }}" class="d-inline cancel-lesson-form">
    @csrf
    <button type="button"
        class="btn btn-outline-danger btn-sm open-cancel-modal"
        data-lesson-id="{{ $lesson->id }}"
        data-lesson-time="{{ $lesson->starts_at?->translatedFormat('H:i — d MMM') }}"
        data-lesson-room="{{ $roomName }}"
        data-lesson-operator="{{ $operatorName }}"
    >Annulla</button>
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
                        @if($isCanceled)
                        <tr class="small text-muted">
            <th>Cliente</th>
            <th>Contatti</th>
            <th>Contattato</th>
            <th style="width:1%"></th>
        </tr>
                        @else
                        <tr class="small text-muted">
                            <th>Cliente</th>
                            <th>Contatti</th>
                            <th>Pacchetto</th>
                            <th>Pagata</th>
                            <th>Presenza</th>
                            <th style="width:1%"></th>
                        </tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach($bookings as $b)
                        @php
    $u = $b->user;
    $fullName = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->email ?? 'Utente #'.$u->id);
    $pkg = $b->userPackage;
    $pkgLabel = $pkg?->package?->name ? ($pkg->package->name.' (rimasti: '.$pkg->lessons_remaining.')') : null;

    $subject = 'Annullamento lezione '.$lesson->starts_at->format('d/m H:i');
    $bodyTxt = 'Ciao '.($u->first_name ?? '').",\nla lezione del ".$lesson->starts_at->format('d/m H:i')." è stata annullata. Contattaci per riprogrammare.";
    $mailto  = $u->email
        ? 'mailto:'.$u->email.'?subject='.rawurlencode($subject).'&body='.rawurlencode($bodyTxt)
        : null;


    $e164     = $u->phone;                                // es. +393331234567
    $waDigits = $e164 ? preg_replace('/\D+/', '', $e164)  // -> 393331234567
                      : null;
    $waLink   = $waDigits ? 'https://wa.me/'.$waDigits.'?text='.rawurlencode($bodyTxt) : null;
@endphp

                            <tr data-booking-id="{{ $b->id }}">
                                <td class="fw-semibold">
    @if($mode === 'admin')
        <a href="{{ route('admin.users.show', $u) }}" class="text-decoration-none">{{ $fullName }}</a>
    @else
        {{ $fullName }}
    @endif
</td>

@if($isCanceled)
  <td class="small">
    @if($u->email)
      <a href="{{ $mailto }}" class="text-decoration-none me-3">
        <i class="fa-solid fa-envelope me-1"></i>{{ $u->email }}
      </a>
    @endif

    @if($u->phone)
      @if($waLink)
        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="text-decoration-none">
          <i class="fa-brands fa-whatsapp me-1"></i>{{ $u->phone }}
        </a>
      @else
        {{-- Numero formattato ma (prob.) non WhatsApp: mostro comunque il telefono --}}
        <span><i class="fa-brands fa-whatsapp me-1"></i>{{ $u->phone }}</span>
      @endif
    @endif

    @unless($u->email || $u->phone)
      <span class="text-muted">—</span>
    @endunless
  </td>

  <td>
    <button type="button"
            class="btn btn-outline-secondary btn-sm toggle-contacted"
            data-url="{{ route('bookings.toggleContacted', $b) }}">
      {{ $b->contacted ? '✓' : 'X' }}
    </button>
  </td>
@else
{{-- ramo normale: contatti + pacchetto + paid + attended --}}
  <td class="small">
    <div>
    <span class="text-muted">Email:</span>
    @if($u->email)
      <a href="mailto:{{ $u->email }}">{{ $u->email }}</a>
    @else
      <span class="text-muted">—</span>
    @endif
    </div>
    <div>
    <span class="text-muted">Tel:</span>
    @if($u->phone)
      @if($waLink)
        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="text-decoration-none">
          <i class="fa-brands fa-whatsapp me-1"></i>{{ $u->phone }}
        </a>
      @else
        {{-- Numero formattato ma (prob.) non WhatsApp: mostro comunque il telefono --}}
        <span><i class="fa-brands fa-whatsapp me-1"></i>{{ $u->phone }}</span>
      @endif
    @endif
    </div>
  </td>
    <td>
        <button class="btn btn-primary btn-sm toggle-paid" data-url="{{ route('bookings.togglePaid', $b) }}" type="button">
            {{ $b->paid ? '✓' : 'X' }}
        </button>
    </td>
    <td>
        <button type="button" class="btn btn-primary btn-sm toggle-attended" data-url="{{ route('bookings.toggleAttended', $b) }}">
            {{ $b->attended ? '✓' : 'X' }}
        </button>
    </td>
@endif

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

    @once
<div class="modal fade" id="lessonCancelModal" tabindex="-1" aria-labelledby="lessonCancelModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="lessonCancelModalLabel">Conferma annullamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>
      <div class="modal-body">
        <p>Sei sicuro di voler annullare questa lezione?</p>
        <ul class="list-unstyled small text-muted mb-0">
          <li><strong>Quando:</strong> <span id="cancel-lesson-time">—</span></li>
          <li><strong>Sala:</strong> <span id="cancel-lesson-room">—</span></li>
          <li><strong>Operatore:</strong> <span id="cancel-lesson-operator">—</span></li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
        <button type="button" class="btn btn-danger" id="confirmLessonCancelBtn">Annulla lezione</button>
      </div>
    </div>
  </div>
</div>
@endonce

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
                            btn.textContent = paid ? '✓' : 'X';
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
                            btn.textContent = attended ? '✓' : 'X';
                        }catch(err){
                            alert(err.response?.message || 'Errore nel toggle attended');
                        }finally{
                            btn.disabled = false;
                        }
                    }

                    // Toggle Contacted
if(t.closest('.toggle-contacted')){
    e.preventDefault();
    const btn = t.closest('.toggle-contacted');
    const url = btn.dataset.url;
    btn.disabled = true;
    try{
        const res = await fetchJSON(url, { method:'POST' });
        const contacted = !!res?.booking?.contacted;
        btn.textContent = contacted ? '✓' : 'X';
    }catch(err){
        alert(err.response?.message || 'Errore nel toggle contacted');
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


            // --- Cancel modal logic ---
let currentCancelForm = null;

const cancelModalEl = document.getElementById('lessonCancelModal');
const cancelModal = cancelModalEl && (window.bootstrap
    ? new window.bootstrap.Modal(cancelModalEl)
    : null);

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.open-cancel-modal');
    if (!btn) return;

    e.preventDefault();
    currentCancelForm = btn.closest('form');

    // Riempie i dettagli
    const tEl = document.getElementById('cancel-lesson-time');
    const rEl = document.getElementById('cancel-lesson-room');
    const oEl = document.getElementById('cancel-lesson-operator');
    if (tEl) tEl.textContent = btn.dataset.lessonTime || '—';
    if (rEl) rEl.textContent = btn.dataset.lessonRoom || '—';
    if (oEl) oEl.textContent = btn.dataset.lessonOperator || '—';

    // Mostra modale (fallback: confirm nativo se bootstrap non è disponibile)
    if (cancelModal) {
        cancelModal.show();
    } else if (confirm('Annullare questa lezione?')) {
        currentCancelForm?.submit();
    }
});

document.getElementById('confirmLessonCancelBtn')?.addEventListener('click', () => {
    if (currentCancelForm) {
        currentCancelForm.submit();
    }
});

        </script>
    @endpush
@endonce
