<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class LessonBookingController extends Controller
{
    public function store(Request $request, Lesson $lesson)
    {
        $actor = Auth::user();
        $isAdmin = $actor->hasRole('admin');
        $isOperator = $actor->hasRole('operatore');

        if ($lesson->canceled) {
            return back()->withErrors('Lezione annullata.');
        }
        if ($lesson->starts_at->isPast()) {
            return back()->withErrors('La lezione è già iniziata o passata.');
        }

        $targetUserId = $actor->hasRole('cliente')
            ? $actor->id
            : $request->input('user_id', $actor->id);

        // Validazione minima del target
        $request->merge(['user_id' => $targetUserId]);
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        // Autorizzazione per ruolo
        if (!$isAdmin && $isOperator) {
            if ((int) $lesson->operator_id !== (int) $actor->id) {
                abort(403, 'Non puoi aggiungere utenti a lezioni di altre operatrici.');
            }
        }
        if (!$isAdmin && !$isOperator) {
            if ((int) $targetUserId !== (int) $actor->id) {
                abort(403, 'Non puoi iscrivere altri utenti.');
            }
        }

        try {
            DB::transaction(function () use ($lesson, $actor, $targetUserId) {
                // Lock sulla lezione per serializzare la capienza
                $locked = Lesson::whereKey($lesson->id)->lockForUpdate()->first();

                // Già iscritto (attivo)?
                $already = LessonUser::where('lesson_id', $locked->id)
                    ->where('user_id', $targetUserId)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($already) {
                    abort(422, 'Sei già iscritto a questa lezione.');
                }

                // Capienza (solo attivi)
                $activeCount = LessonUser::where('lesson_id', $locked->id)
                    ->whereNull('deleted_at')
                    ->count();

                if ($activeCount >= $locked->max_clients) {
                    abort(422, 'La lezione è al completo.');
                }

                LessonUser::create([
                    'lesson_id' => $locked->id,
                    'user_id' => $targetUserId,
                    'attended' => false,
                    'counted' => false,
                    'paid' => false,
                    'added_by_user_id' => $actor->id,
                ]);
            });
        } catch (QueryException $e) {
            // In caso in futuro aggiungiamo un vincolo UNIQUE, intercettiamo 23000
            if ($e->getCode() === '23000') {
                return back()->withErrors('Prenotazione già presente o conflitto di capienza.');
            }
            throw $e;
        }

        return back()->with('status', 'Iscrizione completata.');
    }


    public function destroy(LessonUser $booking)
    {
        $actor = Auth::user();
        $isAdmin = $actor->hasRole('admin');
        $isOperator = $actor->hasRole('operatore');
        $isOwner = (int) $booking->user_id === (int) $actor->id;

        $operatorOwnsLesson = $isOperator && (int) $booking->lesson->operator_id === (int) $actor->id;

        if (!$isAdmin && !$operatorOwnsLesson && !$isOwner) {
            abort(403);
        }

        // Regola 6 ore lavorative solo per i clienti che cancellano sé stessi
        if ($isOwner && !$isAdmin && !$isOperator) {
            $tz = config('app.timezone', 'Europe/Rome');
            $nowRome = now($tz);
            if (!$this->clientCanCancel($booking->lesson, $nowRome, 6, $tz)) {
                return back()->withErrors('Cancellazione non consentita: servono almeno 6 ore lavorative (09:00–21:00) prima dell’inizio.');
            }
        }

        $booking->delete(); // soft delete = annullata
        return back()->with('status', 'Prenotazione annullata.');
    }

    private function clientCanCancel(Lesson $lesson, Carbon $nowRome, int $requiredHours = 6, string $tz = 'Europe/Rome'): bool
    {
        $startRome = $lesson->starts_at->copy()->setTimezone($tz);
        if ($nowRome->gte($startRome)) {
            return false; // già iniziata/passata
        }

        $workingMinutes = $this->workingMinutesBetween($nowRome, $startRome, 9, 21);
        return $workingMinutes >= ($requiredHours * 60);
    }

    private function workingMinutesBetween(Carbon $fromRome, Carbon $toRome, int $windowStartHour, int $windowEndHour): int
    {
        if ($fromRome->gte($toRome)) {
            return 0;
        }

        $minutes = 0;
        $day = $fromRome->copy()->startOfDay();
        $lastDay = $toRome->copy()->startOfDay();

        while ($day->lte($lastDay)) {
            $dayStart = $day->copy()->addHours($windowStartHour); // 09:00
            $dayEnd = $day->copy()->addHours($windowEndHour);   // 21:00

            // Intervallo utile di questo giorno = [max(dayStart, from), min(dayEnd, to)]
            $periodStart = $fromRome->greaterThan($dayStart) ? $fromRome->copy() : $dayStart;
            $periodEnd = $toRome->lessThan($dayEnd) ? $toRome->copy() : $dayEnd;

            if ($periodEnd->gt($periodStart)) {
                $minutes += $periodEnd->diffInMinutes($periodStart);
            }

            $day->addDay();
        }

        return $minutes;
    }
}
