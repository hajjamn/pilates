<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonUser;
use App\Models\UserPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use DateTimeInterface;

class BookingService
{
    /**
     * Aggiunge un cliente ad una lezione.
     *
     * @param  Lesson                $lesson
     * @param  int                   $clientUserId
     * @param  bool                  $markPaid
     * @param  bool                  $usePackage
     * @param  int|null              $userPackageId
     * @param  User                  $actor
     * @param  int|null              $paidToUserId         (opzionale) a chi è stato pagato
     * @param  float|null            $lessonPriceOverride  (opzionale) prezzo snapshot
     * @param  Carbon|DateTimeInterface|string|null $paidAtOverride (opz.) quando è stato pagato
     */
    public function addBooking(
        Lesson $lesson,
        int $clientUserId,
        bool $markPaid,
        bool $usePackage,
        ?int $userPackageId,
        User $actor,
        ?int $paidToUserId = null,
        ?float $lessonPriceOverride = null,
        $paidAtOverride = null
    ): LessonUser {
        return DB::transaction(function () use ($lesson, $clientUserId, $markPaid, $usePackage, $userPackageId, $actor, $paidToUserId, $lessonPriceOverride, $paidAtOverride) {
            if ($lesson->canceled) {
                throw ValidationException::withMessages([
                    'lesson' => 'La lezione è annullata: impossibile aggiungere iscritti.',
                ]);
            }

            // Doppione attivo?
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

            // Capienza solo per FUTURE e se non c'è override
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
                    ->lockForUpdate()
                    ->first();

                if (!$userPackage || $userPackage->lessons_remaining <= 0) {
                    throw ValidationException::withMessages([
                        'user_package_id' => 'Pacchetto non disponibile o senza crediti.',
                    ]);
                }

                // Consuma 1 credito
                $userPackage->decrement('lessons_remaining');
            }

            // Pagamento snapshot (solo se NON coperta da pacchetto e richiesto)
            $shouldMarkPaidNow = $markPaid && !$usePackage;

            $paidAt = $shouldMarkPaidNow ? $this->normalizePaidAt($paidAtOverride) : null;
            $paidTo = $shouldMarkPaidNow ? ($paidToUserId ?? $actor->id) : null;
            $snapPrice = $shouldMarkPaidNow
                ? ($lessonPriceOverride ?? (float) config('billing.lesson_price', 0.0))
                : null;

            /** @var LessonUser $booking */
            $booking = LessonUser::create([
                'lesson_id' => $lesson->id,
                'user_id' => $clientUserId,
                'added_by_user_id' => $actor->id,

                // Compat UI attuale
                'paid' => (bool) $shouldMarkPaidNow,
                'paid_to_user_id' => $paidTo,

                'user_package_id' => $userPackage?->id,
                'counted' => $userPackage ? true : false,

                // Nuovi campi
                'paid_at' => $paidAt,
                'lesson_price' => $snapPrice,
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
                $booking->counted = false;
            }
            $booking->save();
            $booking->delete(); // SoftDeletes
        });
    }

    public function toggleAttended(LessonUser $booking): LessonUser
    {
        $booking->attended = !$booking->attended;
        $booking->save();

        return $booking->fresh(['user', 'userPackage.package']);
    }

    /**
     * Toggle pagato/non pagato.
     * - Quando diventa "pagato": setta paid_at (override se fornito) e fotografa il prezzo corrente se assente.
     * - Quando torna "non pagato": azzera paid_at e paid_to_user_id.
     *
     * @param  Carbon|DateTimeInterface|string|null $paidAtOverride
     */

    public function togglePaid(LessonUser $booking, User $actor, $paidAtOverride = null, ?int $paidToUserId = null): LessonUser
    {
        $isBecomingPaid = !$booking->paid;

        if ($isBecomingPaid) {
            // Passa a PAGATO
            $booking->paid = true;
            $booking->paid_to_user_id = $paidToUserId ?? $actor->id;   // ← usa quello passato o l'attore
            $booking->paid_at = $this->normalizePaidAt($paidAtOverride);

            if ($booking->lesson_price === null) {
                $booking->lesson_price = (float) config('billing.lesson_price', 0.0);
            }
        } else {
            // Torna a NON PAGATO
            $booking->paid = false;
            $booking->paid_to_user_id = null;
            $booking->paid_at = null;
            // lesson_price resta come storico
        }

        $booking->save();

        return $booking->fresh(['user', 'userPackage.package']);
    }


    /**
     * Normalizza l'input di paid_at.
     *
     * @param  Carbon|DateTimeInterface|string|null $value
     * @return Carbon|null
     */
    private function normalizePaidAt($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }
        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }
        return now();
    }
}
