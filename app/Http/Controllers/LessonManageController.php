<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonRequest;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LessonManageController extends Controller
{
    // crea manualmente una lezione
    public function store(StoreLessonRequest $request)
    {
        $user = Auth::user();

        $data = $request->validated();

        // Operatore: può creare solo per sé stesso
        if ($user->hasRole('operatore') && !$user->hasRole('admin')) {
            $data['operator_id'] = $user->id;
        } else {
            // admin: se non passato, può impostare a sé o lasciarlo null (meglio richiederlo in form)
            $data['operator_id'] = $data['operator_id'] ?? $user->id;
        }

        $lesson = Lesson::create([
            'room_id' => $data['room_id'],
            'operator_id' => $data['operator_id'],
            'starts_at' => $data['starts_at'],
            'max_clients' => $data['max_clients'],
            'canceled' => false,
            'manual_override' => true, // creazione manuale ⇒ abilita override capienza se vuoi
        ]);

        return back()->with('status', 'Lezione creata.');
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
        return response('LESSON EDIT (full) placeholder — ID: ' . $lesson->id, 200);
    }

    public function editLite(Lesson $lesson)
    {
        return response('LESSON EDIT-LITE placeholder — ID: ' . $lesson->id, 200);
    }

    public function update(Request $request, Lesson $lesson)
    {
        return back()->with('status', 'Update placeholder eseguito (nessuna modifica salvata).');
    }
}
