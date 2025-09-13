<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonRequest;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LessonManageController extends Controller
{
    // crea manualmente una lezione
    public function store(StoreLessonRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // Operatore semplice → forza se stesso
        if ($user->hasRole('operatore') && !$user->hasRole('admin')) {
            $data['operator_id'] = $user->id;
        } else {
            // Admin: se non passato, di default assegna a sé
            $data['operator_id'] = $data['operator_id'] ?? $user->id;
        }

        // ----------------------------
        // CHECK CONFLITTI APP-LEVEL
        // ----------------------------
        $roomConflict = Lesson::where('room_id', $data['room_id'])
            ->where('starts_at', $data['starts_at'])
            ->where('canceled', false)
            ->whereNull('deleted_at')
            ->exists();

        if ($roomConflict) {
            return back()
                ->withErrors(['starts_at' => 'Conflitto: esiste già una lezione in questa sala a quell’ora.'])
                ->withInput();
        }

        $operatorConflict = Lesson::where('operator_id', $data['operator_id'])
            ->where('starts_at', $data['starts_at'])
            ->where('canceled', false)
            ->whereNull('deleted_at')
            ->exists();

        if ($operatorConflict) {
            return back()
                ->withErrors(['starts_at' => 'Conflitto: l’operatore è già assegnato a un’altra lezione a quell’ora.'])
                ->withInput();
        }

        // ----------------------------
        // CREAZIONE
        // ----------------------------
        $lesson = Lesson::create([
            'room_id' => $data['room_id'],
            'operator_id' => $data['operator_id'],
            'starts_at' => $data['starts_at'],
            'max_clients' => $data['max_clients'],
            'canceled' => false,
            'manual_override' => true,
        ]);

        return redirect()
            ->route('lessons.show', $lesson)
            ->with('status', 'Lezione creata.');
    }


    // annulla (non elimina) la lezione
    public function cancel(Lesson $lesson)
    {
        $user = Auth::user();
        if (!$this->canManageLesson($user, $lesson)) {
            abort(403);
        }

        $lesson->update(['canceled' => true]);

        return back()->with('status', 'Lezione annullata.');
    }

    // ripristina una lezione annullata
    public function uncancel(Lesson $lesson)
    {
        $user = Auth::user();
        if (!$this->canManageLesson($user, $lesson)) {
            abort(403);
        }

        $lesson->update(['canceled' => false]);

        return back()->with('status', 'Lezione ripristinata.');
    }

    // hard delete (solo admin), evita per uso quotidiano
    public function destroy(Lesson $lesson)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $lesson->delete(); // SoftDeletes sul modello; elimina "amministrativamente"
        return back()->with('status', 'Lezione eliminata.');
    }

    private function canManageLesson($actor, Lesson $lesson): bool
    {
        return $actor->hasRole('admin') || ($actor->hasRole('operatore') && (int) $lesson->operator_id === (int) $actor->id);
    }

    public function show(Lesson $lesson)
    {
        $user = Auth::user();

        $isAdmin = $user->hasRole('admin');
        $isOperatorOwner = $user->hasRole('operatore') && (int) $lesson->operator_id === (int) $user->id;
        abort_unless($isAdmin || $isOperatorOwner, 403);

        $lesson->load([
            'room:id,name',
            'operator:id,first_name,last_name,email',
            'lessonUsers.user:id,first_name,last_name,email,phone',
        ])->loadCount('clients');

        $mode = $isAdmin ? 'admin' : 'operator';

        return view('manage.lessons.show', [
            'lesson' => $lesson,
            'mode' => $mode,
        ]);
    }


    public function edit(Lesson $lesson)
    {
        $user = Auth::user();
        abort_unless($user->hasRole('admin'), 403);

        // Dati per il form
        $lesson->load([
            'room:id,name',
            'operator:id,first_name,last_name,email',
        ]);

        // Liste selezioni
        $rooms = Room::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Solo utenti con ruolo "operatore" (Spatie)
        $operators = User::role('operatore')
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('manage.lessons.edit', [
            'lesson' => $lesson,
            'rooms' => $rooms,
            'operators' => $operators,
            'mode' => 'admin',
        ]);
    }

    public function editLite(Lesson $lesson)
    {
        $user = Auth::user();

        // Solo operatore proprietario (NON admin)
        if (!($user->hasRole('operatore') && !$user->hasRole('admin'))) {
            abort(403);
        }

        if ((int) $lesson->operator_id !== (int) $user->id) {
            abort(403);
        }

        // Carico dati minimali
        $lesson->load('room:id,name');

        return view('manage.lessons.edit-lite', [
            'lesson' => $lesson,
            'mode' => 'operator',
        ]);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $user = Auth::user();
        abort_unless($user->hasRole('admin'), 403);

        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'operator_id' => ['required', 'exists:users,id'],
            'starts_at' => ['required', 'date'],
            'max_clients' => ['required', 'integer', 'min:1', 'max:200'],
            'canceled' => ['sometimes', 'boolean'],
            'manual_override' => ['sometimes', 'boolean'],
        ]);

        // Normalizza boolean
        $data['canceled'] = $request->boolean('canceled');
        $data['manual_override'] = $request->boolean('manual_override');

        // 1) Capienza >= iscritti attuali
        $currentBooked = $lesson->clients()->count();
        if ($data['max_clients'] < $currentBooked) {
            return back()->withErrors([
                'max_clients' => "Capienza inferiore agli iscritti attuali ({$currentBooked}).",
            ])->withInput();
        }

        // 2) Conflitti: stessa sala o stesso operatore alla stessa starts_at
        $roomConflict = Lesson::where('id', '!=', $lesson->id)
            ->where('room_id', $data['room_id'])
            ->where('starts_at', $data['starts_at'])
            ->exists();

        if ($roomConflict) {
            return back()->withErrors([
                'starts_at' => 'Conflitto: esiste già una lezione in questa sala a quell’ora.',
            ])->withInput();
        }

        $operatorConflict = Lesson::where('id', '!=', $lesson->id)
            ->where('operator_id', $data['operator_id'])
            ->where('starts_at', $data['starts_at'])
            ->exists();

        if ($operatorConflict) {
            return back()->withErrors([
                'starts_at' => 'Conflitto: l’operatore è già assegnato a un’altra lezione a quell’ora.',
            ])->withInput();
        }

        // 3) Salvataggio
        $lesson->update($data);

        return redirect()
            ->route('lessons.show', $lesson)
            ->with('status', 'Lezione aggiornata.');
    }

    public function updateLite(Request $request, Lesson $lesson)
    {
        $user = Auth::user();

        // Solo operatore (non admin) proprietario
        if (!($user->hasRole('operatore') && !$user->hasRole('admin'))) {
            abort(403);
        }
        if ((int) $lesson->operator_id !== (int) $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'max_clients' => ['required', 'integer', 'min:1', 'max:200'],
            'canceled' => ['sometimes', 'boolean'],
        ]);

        $data['canceled'] = $request->boolean('canceled');

        // 1) Capienza >= iscritti attuali
        $currentBooked = $lesson->clients()->count();
        if ($data['max_clients'] < $currentBooked) {
            return back()->withErrors([
                'max_clients' => "Capienza inferiore agli iscritti attuali ({$currentBooked}).",
            ])->withInput();
        }

        // 2) Conflitti alla stessa starts_at (stessa sala e stesso operatore)
        //   NB: l’operatore non può cambiare room/operator, quindi controlliamo con i valori correnti
        $roomConflict = Lesson::where('id', '!=', $lesson->id)
            ->where('room_id', $lesson->room_id)
            ->where('starts_at', $data['starts_at'])
            ->exists();

        if ($roomConflict) {
            return back()->withErrors([
                'starts_at' => 'Conflitto: esiste già una lezione in questa sala a quell’ora.',
            ])->withInput();
        }

        $operatorConflict = Lesson::where('id', '!=', $lesson->id)
            ->where('operator_id', $lesson->operator_id)
            ->where('starts_at', $data['starts_at'])
            ->exists();

        if ($operatorConflict) {
            return back()->withErrors([
                'starts_at' => 'Conflitto: sei già assegnato a un’altra lezione a quell’ora.',
            ])->withInput();
        }

        // 3) Salvataggio: whitelist campi consentiti
        $lesson->update([
            'starts_at' => $data['starts_at'],
            'max_clients' => $data['max_clients'],
            'canceled' => $data['canceled'],
        ]);

        return redirect()
            ->route('lessons.show', $lesson)
            ->with('status', 'Lezione aggiornata.');
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->hasRole('admin'), 403);

        // Liste selezioni
        $rooms = Room::select('id', 'name')->orderBy('name')->get();
        $operators = User::role('operatore')
            ->select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')->orderBy('last_name')->get();

        // Default opzionali dalla query (?room_id= … &operator_id= … &starts_at= …)
        $defaults = [
            'room_id' => $request->query('room_id'),
            'operator_id' => $request->query('operator_id'),
            'starts_at' => $request->query('starts_at'),
            'max_clients' => 7,
        ];

        return view('manage.lessons.create', [
            'rooms' => $rooms,
            'operators' => $operators,
            'defaults' => $defaults,
            'mode' => 'admin',
        ]);
    }


}
