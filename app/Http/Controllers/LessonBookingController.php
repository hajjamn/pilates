<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManagedBookingRequest;
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
use App\Services\BookingService;

class LessonBookingController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

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

    public function storeManaged(ManagedBookingRequest $request, Lesson $lesson)
    {
        $actor = Auth::user();

        if (!$this->canManageLesson($actor, $lesson)) {
            abort(403);
        }

        try {
            $booking = $this->bookingService->addBooking(
                lesson: $lesson,
                clientUserId: (int) $request->integer('user_id'),
                markPaid: (bool) $request->boolean('paid'),
                usePackage: (bool) $request->boolean('use_package'),
                userPackageId: $request->input('user_package_id'),
                actor: $actor
            );
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'booking' => $booking,
                'message' => 'Cliente aggiunto alla lezione.',
            ]);
        }

        return back()->with('status', 'Cliente aggiunto alla lezione.');
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

    public function toggleAttended(LessonUser $booking)
    {
        $actor = Auth::user();
        $lesson = $booking->lesson;

        if (!$this->canManageLesson($actor, $lesson)) {
            abort(403);
        }

        $booking = $this->bookingService->toggleAttended($booking);

        return response()->json([
            'ok' => true,
            'booking' => $booking,
        ]);
    }

    public function togglePaid(LessonUser $booking)
    {
        $actor = Auth::user();
        $lesson = $booking->lesson;

        if (!$this->canManageLesson($actor, $lesson)) {
            abort(403);
        }

        $booking = $this->bookingService->togglePaid($booking, $actor);

        return response()->json([
            'ok' => true,
            'booking' => $booking,
        ]);
    }

    public function toggleContacted(LessonUser $booking)
    {
        $actor = Auth::user();
        $lesson = $booking->lesson;

        if (!$this->canManageLesson($actor, $lesson)) {
            abort(403);
        }

        $booking->contacted = !$booking->contacted;
        $booking->save();

        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'contacted' => (bool) $booking->contacted,
            ],
        ]);
    }
    public function searchClients(Request $request)
    {
        $actor = Auth::user();
        if (!$actor->hasAnyRole(['operatore', 'admin'])) {
            abort(403);
        }

        $q = trim((string) $request->query('q', ''));

        $users = User::role('cliente')
            ->when($q !== '', function ($qq) use ($q) {
                $like = '%' . str_replace(' ', '%', $q) . '%';
                $qq->where(function ($w) use ($like) {
                    $w->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        $packages = UserPackage::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('lessons_remaining', '>', 0)
            ->with('package:id,name')
            ->get(['id', 'user_id', 'package_id', 'lessons_remaining']);

        $byUser = $packages->groupBy('user_id');

        $result = $users->map(function ($u) use ($byUser) {
            return [
                'id' => $u->id,
                'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                'email' => $u->email,
                'phone' => $u->phone,
                'packages' => ($byUser[$u->id] ?? collect())->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'label' => $p->package?->name . ' (rimasti: ' . $p->lessons_remaining . ')',
                        'lessons_remaining' => $p->lessons_remaining,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json(['ok' => true, 'data' => $result]);
    }

    // ===== Helpers esistenti/nuovi =====

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

    private function canManageLesson(User $actor, Lesson $lesson): bool
    {
        return $actor->hasRole('admin')
            || ($actor->hasRole('operatore') && (int) $lesson->operator_id === (int) $actor->id);
    }
}
