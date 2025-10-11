<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Lesson;
use App\Models\Room;

class LessonCalendarController extends Controller
{
    public function index(Request $request)
    {
        $dayParam = $request->query('day');
        $monthParam = $request->query('month');
        $weekParam = $request->query('week');
        $roomId = $request->query('room_id');
        $operatorId = $request->query('operator_id');

        $selectedDay = $this->parseDayOrToday($dayParam);
        $weekStart = $this->parseWeekStartOrFromDay($weekParam, $selectedDay);
        $weekDays = $this->getWeekDays($weekStart);


        $weekMonths = collect($weekDays)->map(fn($d) => $d->format('Y-m'))->unique();
        $contextMonth = $weekMonths->count() === 1
            ? Carbon::createFromFormat('Y-m', $weekMonths->first())->startOfMonth()
            : $this->parseMonthOrFromDay($monthParam, $selectedDay);

        $user = Auth::user();
        $mode = $user->hasRole('admin') ? 'admin' : ($user->hasRole('operatore') ? 'operator' : 'client');

        $rooms = Room::query()
            ->orderBy('name')
            ->get(['id', 'name', 'max_clients']);

        if ($roomId && !$rooms->pluck('id')->contains((int) $roomId))
            $roomId = null;

        $operators = collect();
        if (in_array($mode, ['admin', 'client'])) {
            $operators = User::role('operatore')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']);
        }

        if ($operatorId && !$operators->pluck('id')->contains((int) $operatorId)) {
            $operatorId = null;
        }

        $lessonsQuery = Lesson::query()
            ->visibleTo($user)
            ->onDay($selectedDay)
            ->inRoom($roomId)
            ->when(
                in_array($mode, ['admin', 'client']) && $operatorId,
                fn($q) =>
                $q->where('operator_id', $operatorId)
            )
            ->with(['room', 'operator'])
            ->withCount('clients')
            ->orderBy('starts_at');

        $monthIso = $contextMonth->format('Y-m');
        $monthLabel = ucfirst($contextMonth->translatedFormat('F'));
        $selectedIso = $selectedDay->toDateString();
        $weekStartIso = $weekStart->toDateString();

        if ($mode === 'client') {
            $lessonsQuery->withCount([
                'clients as is_booked' => function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                }
            ]);
        }

        if (in_array($mode, ['admin', 'operator'])) {
            // Carico i booking con utente e pacchetto per le card di gestione
            $lessonsQuery->with([
                'lessonUsers' => fn($q) => $q->active()->with([
                    'user:id,first_name,last_name,email,phone',
                    'userPackage:id,package_id,lessons_remaining',
                    'userPackage.package:id,name',
                ])->orderBy('id'), // opzionale
                'room:id,name',
                'operator:id,first_name,last_name,email',
            ]);
            $lessonsQuery->withCount(['cashBookingsByPackOwners as red_flag_count']);
        } else {
            $lessonsQuery->with(['room', 'operator']);
        }
        // ... e poi:
        $lessons = $lessonsQuery->get();

        return view('calendar.index', [
            'mode' => $mode,
            'monthIso' => $monthIso,
            'monthLabel' => $monthLabel,
            'selectedDay' => $selectedIso,
            'weekStart' => $weekStartIso,
            'weekDays' => $weekDays,
            'roomId' => $roomId,
            'rooms' => $rooms,
            'lessons' => $lessons,
            'operators' => $operators,
            'operatorId' => $operatorId
        ]);
    }

    // ---------- Helpers ----------
    private function parseDayOrToday(?string $dayParam): Carbon
    {
        if ($dayParam) {
            try {
                return Carbon::createFromFormat('Y-m-d', $dayParam)->startOfDay();
            } catch (\Throwable $e) {
            }
        }
        return now()->startOfDay();
    }

    private function parseMonthOrFromDay(?string $monthParam, Carbon $day): Carbon
    {
        if ($monthParam) {
            try {
                return Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
            } catch (\Throwable $e) {
            }
        }
        return $day->copy()->startOfMonth();
    }

    private function parseWeekStartOrFromDay(?string $weekParam, Carbon $day): Carbon
    {
        if ($weekParam) {
            try {
                return Carbon::createFromFormat('Y-m-d', $weekParam)->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable $e) {
            }
        }
        return $day->copy()->startOfWeek(Carbon::MONDAY);
    }

    private function getWeekDays(Carbon $weekStart): array
    {
        return collect()->range(0, 6)->map(fn($i) => $weekStart->copy()->addDays($i))->all();
    }
}
