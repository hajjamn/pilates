<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;
use App\Models\LessonUser;
use App\Models\Room;
use App\Models\User;

class ClientLessonController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        // ==== Filtri ====
        $validated = $request->validate([
            'time' => 'nullable|in:future,past,all',
            'status' => 'nullable|in:booked,canceled,all',
            'room_id' => 'nullable|integer',
            'operator_id' => 'nullable|integer',
        ]);

        $time = $validated['time'] ?? 'future';   // default: future
        $status = $validated['status'] ?? 'booked';   // default: prenotazioni attive
        $roomId = $validated['room_id'] ?? null;
        $operatorId = $validated['operator_id'] ?? null;

        // ==== Query su Lesson con join sulla pivot dell'utente ====
        $q = Lesson::query()
            ->join('lesson_users as lu', 'lu.lesson_id', '=', 'lessons.id')
            ->where('lu.user_id', $user->id);

        // Stato prenotazione (attiva / cancellata / tutte)
        if ($status === 'booked') {
            $q->whereNull('lu.deleted_at');
        } elseif ($status === 'canceled') {
            $q->whereNotNull('lu.deleted_at');
        } // 'all' -> nessun filtro

        // Filtro temporale
        if ($time === 'future') {
            $q->where('lessons.starts_at', '>', now());
        } elseif ($time === 'past') {
            $q->where('lessons.starts_at', '<=', now());
        }

        // Filtro sala / operatore
        if (!empty($roomId)) {
            $q->where('lessons.room_id', (int) $roomId);
        }
        if (!empty($operatorId)) {
            $q->where('lessons.operator_id', (int) $operatorId);
        }

        // Evita eventuali duplicati (in caso di più righe storiche in pivot)
        $q->distinct('lessons.id');

        // Seleziona solo colonne della lezione (Eloquent modellerà Lesson correttamente)
        $q->select('lessons.*');

        // Ordinamento e eager load
        $q->orderBy('lessons.starts_at', 'desc')
            ->with(['operator', 'room']);

        // Paginazione compatibile e mantenimento query string
        $lessons = $q->paginate(12);
        $lessons->appends($request->query());

        // Liste per i filtri
        $rooms = Room::orderBy('name')->get(['id', 'name']);
        $operators = User::operators()
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        return view('client.lessons.index', [
            'lessons' => $lessons,  // paginator di Lesson (niente mapping)
            'time' => $time,
            'status' => $status,
            'roomId' => $roomId,
            'operatorId' => $operatorId,
            'rooms' => $rooms,
            'operators' => $operators,
        ]);
    }


    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // 1) Carico la lezione SOLO se è del cliente corrente (via relazione many-to-many)
        //    Così otteniamo anche il "pivot" con paid/attended, ecc. (dalla definizione in User::lessonsAsClient()).
        $lesson = $user->lessonsAsClient()
            ->with(['operator', 'room'])
            ->whereKey($lesson->getKey())
            ->firstOrFail();

        // 2) Provo a leggere direttamente dal pivot (definito in User::lessonsAsClient()).
        $paid = data_get($lesson, 'pivot.paid');
        $attended = data_get($lesson, 'pivot.attended');

        // 3) Per azioni (annulla prenotazione) ci serve l'ID del booking (= LessonUser).
        //    Lo recupero come modello attivo, filtrando per le chiavi.
        $booking = LessonUser::query()
            ->with(['userPackage.package'])   // ⬅️ carica il pacchetto e il suo "package" (nome)
            ->active()
            ->forLesson($lesson->getKey())
            ->forUser($user->getKey())
            ->first();

        // 4) Stato annullamento lezione (il tuo modello usa 'canceled' boolean).
        $isLessonCanceled = (bool) ($lesson->canceled ?? false);

        $paidViaPackage = (bool) ($booking?->counted && $booking?->user_package_id);
        $packageName = $paidViaPackage ? ($booking?->userPackage?->package?->name ?? 'Pacchetto') : null;

        return view('client.lessons.show', compact(
            'lesson',
            'paid',
            'attended',
            'booking',
            'isLessonCanceled',
            'paidViaPackage',
            'packageName',
        ));
    }
}
