<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonUser;
use App\Models\UserPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Aggiunge un cliente ad una lezione.
     * - Consente aggiunta a lezioni passate.
     * - Vietata su lezioni cancellate.
     * - Capienza: blocco solo per lezioni future (a meno di manual_override).
     * - Pacchetto: se passato, decrementa in modo atomico e marca counted=true.
     */
    public function addBooking(
        Lesson $lesson,
        int $clientUserId,
        bool $markPaid,
        bool $usePackage,
        ?int $userPackageId,
        User $actor
    ): LessonUser {
        return DB::transaction(function () use ($lesson, $clientUserId, $markPaid, $usePackage, $userPackageId, $actor) {

            if ($lesson->canceled) {
                throw ValidationException::withMessages([
                    'lesson' => 'La lezione è annullata: impossibile aggiungere iscritti.',
                ]);
            }

            // duplicate active booking?
            $exists = LessonUser::query()
                ->active()
                ->forLesson($lesson->id)
                ->forUser($clientUserId)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'user_id' => 'Questo cliente è già iscritto alla lezione.',
                ]);
            }

            // capienza solo per FUTURE e se non c'è override
            $isFuture = $lesson->starts_at && $lesson->starts_at->isFuture();
            if ($isFuture && !$lesson->manual_override) {
                $activeCount = LessonUser::query()->active()->forLesson($lesson->id)->count();
                if ($lesson->max_clients !== null && $activeCount >= $lesson->max_clients) {
                    throw ValidationException::withMessages([
                        'lesson' => 'Capienza massima raggiunta.',
                    ]);
                }
            }

            $userPackage = null;
            if ($usePackage) {
                if (!$userPackageId) {
                    throw ValidationException::withMessages([
                        'user_package_id' => 'Seleziona un pacchetto valido.',
                    ]);
                }
                /** @var UserPackage $userPackage */
                $userPackage = UserPackage::query()
                    ->whereKey($userPackageId)
                    ->where('user_id', $clientUserId)
                    ->lockForUpdate() // evitiamo race conditions sui crediti
                    ->first();

                if (!$userPackage || $userPackage->lessons_remaining <= 0) {
                    throw ValidationException::withMessages([
                        'user_package_id' => 'Pacchetto non disponibile o senza crediti.',
                    ]);
                }

                // consume one credit
                $userPackage->decrement('lessons_remaining');
            }

            /** @var LessonUser $booking */
            $booking = LessonUser::create([
                'lesson_id' => $lesson->id,
                'user_id' => $clientUserId,
                'added_by_user_id' => $actor->id,

                'paid' => (bool) $markPaid,
                'paid_to_user_id' => $markPaid ? $actor->id : null,

                'user_package_id' => $userPackage?->id,
                'counted' => $userPackage ? true : false,

                // attended lo lasciamo null/false: verrà gestito da toggle o da job automatici
            ]);

            return $booking->load(['user', 'userPackage.package']);
        });
    }

    /**
     * Rimuove (soft delete) un booking. Se aveva consumato un pacchetto (counted=true),
     * ripristina 1 credito.
     */
    public function removeBooking(LessonUser $booking): void
    {
        DB::transaction(function () use ($booking) {
            if ($booking->counted && $booking->user_package_id) {
                $pkg = UserPackage::query()->lockForUpdate()->find($booking->user_package_id);
                if ($pkg) {
                    $pkg->increment('lessons_remaining');
                }
                // opzionale: azzeri flagged per chiarezza storica (rimane nel record soft-deleted)
                $booking->counted = false;
            }
            $booking->save();
            $booking->delete(); // SoftDeletes sul pivot
        });
    }

    public function toggleAttended(LessonUser $booking): LessonUser
    {
        $booking->attended = !$booking->attended;
        $booking->save();

        return $booking->fresh(['user', 'userPackage.package']);
    }

    public function togglePaid(LessonUser $booking, User $actor): LessonUser
    {
        $booking->paid = !$booking->paid;
        $booking->paid_to_user_id = $booking->paid ? $actor->id : null;
        $booking->save();

        return $booking->fresh(['user', 'userPackage.package']);
    }
}
