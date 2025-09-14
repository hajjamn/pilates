<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;
use App\Models\LessonUser;

class ClientLessonController extends Controller
{
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
