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
use App\Models\UserPackage;
use Illuminate\Validation\ValidationException;

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

        // Validazione minima
        $request->merge(['user_id' => $targetUserId]);
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'use_package' => ['nullable', 'boolean'],
            'user_package_id' => ['nullable', 'integer'], // controllo ownership più sotto
        ]);

        // Autorizzazioni
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

        $usePackage = (bool) $request->boolean('use_package');

        try {
            DB::transaction(function () use ($lesson, $actor, $targetUserId, $usePackage, $request) {
                // 1) Lock lezione per serializzare capienza
                $lockedLesson = Lesson::whereKey($lesson->id)->lockForUpdate()->first();

                // 2) Doppione?
                $already = LessonUser::where('lesson_id', $lockedLesson->id)
                    ->where('user_id', $targetUserId)
                    ->whereNull('deleted_at')
                    ->exists();
                if ($already) {
                    abort(422, 'Sei già iscritto a questa lezione.');
                }

                // 3) Capienza
                $activeCount = LessonUser::where('lesson_id', $lockedLesson->id)
                    ->whereNull('deleted_at')
                    ->count();
                if ($activeCount >= $lockedLesson->max_clients) {
                    abort(422, 'La lezione è al completo.');
                }

                // 4) Se richiesto, blocca un pacchetto dell’utente e scala 1
                $userPackageId = null;
                $counted = false;

                if ($usePackage) {
                    // Se passato, deve appartenere al target e avere credito
                    if ($request->filled('user_package_id')) {
                        $pkg = UserPackage::where('id', $request->integer('user_package_id'))
                            ->where('user_id', $targetUserId)
                            ->lockForUpdate()
                            ->first();
                        if (!$pkg) {
                            throw ValidationException::withMessages([
                                'user_package_id' => 'Pacchetto non valido per questo utente.',
                            ]);
                        }
                    } else {
                        // Altrimenti prendi il più vecchio attivo
                        $pkg = UserPackage::where('user_id', $targetUserId)
                            ->where('lessons_remaining', '>', 0)
                            ->orderBy('purchased_at')
                            ->lockForUpdate()
                            ->first();
                        if (!$pkg) {
                            throw ValidationException::withMessages([
                                'use_package' => 'Non hai crediti disponibili nei pacchetti.',
                            ]);
                        }
                    }

                    if ($pkg->lessons_remaining <= 0) {
                        throw ValidationException::withMessages([
                            'use_package' => 'Il pacchetto selezionato non ha crediti disponibili.',
                        ]);
                    }

                    // Scala 1 credito
                    $pkg->decrement('lessons_remaining');
                    $userPackageId = $pkg->id;
                    $counted = true;
                }

                // 5) Crea prenotazione
                LessonUser::create([
                    'lesson_id' => $lockedLesson->id,
                    'user_id' => $targetUserId,
                    'attended' => false,
                    'counted' => $counted,
                    'paid' => false,
                    'user_package_id' => $userPackageId,
                    'added_by_user_id' => $actor->id,
                ]);
            });
        } catch (ValidationException $ve) {
            return back()->withErrors($ve->errors());
        } catch (QueryException $e) {
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

        DB::transaction(function () use ($booking) {
            $lesson = Lesson::whereKey($booking->lesson_id)->lockForUpdate()->first();

            // Rimborso se:
            // - la prenotazione aveva consumato un pacchetto (counted = true, user_package_id != null)
            // - la lezione NON è ancora iniziata
            if ($booking->counted && $booking->user_package_id && $lesson->starts_at->isFuture()) {
                $pkg = UserPackage::where('id', $booking->user_package_id)->lockForUpdate()->first();
                if ($pkg) {
                    $pkg->increment('lessons_remaining'); // restituisci 1 credito
                }
                // segna che non è più conteggiata
                $booking->update(['counted' => false]);
            }

            // Soft delete = annullata
            $booking->delete();
        });

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
