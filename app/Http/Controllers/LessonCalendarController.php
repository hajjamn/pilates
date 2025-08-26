<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Lesson;
use App\Models\Room;

class LessonCalendarController extends Controller
{
    public function index(Request $request)
    {
        $dayParam = $request->query('day');       // 'YYYY-MM-DD' (giorno selezionato)
        $monthParam = $request->query('month');     // 'YYYY-MM'    (mese per il pill)
        $weekParam = $request->query('week');      // 'YYYY-MM-DD' (lunedì della settimana mostrata)
        $roomId = $request->query('room_id');

        $selectedDay = $this->parseDayOrToday($dayParam);                // Carbon
        $weekStart = $this->parseWeekStartOrFromDay($weekParam, $selectedDay); // Carbon (Lunedì)
        $weekDays = $this->getWeekDays($weekStart);                   // array[7] Carbon

        // Mese di contesto: cambia solo se tutti i 7 giorni sono nello stesso mese
        $weekMonths = collect($weekDays)->map(fn($d) => $d->format('Y-m'))->unique();
        $contextMonth = $weekMonths->count() === 1
            ? Carbon::createFromFormat('Y-m', $weekMonths->first())->startOfMonth()
            : $this->parseMonthOrFromDay($monthParam, $selectedDay);

        $user = Auth::user();
        $mode = $user->hasRole('admin') ? 'admin' : ($user->hasRole('operatore') ? 'operator' : 'client');

        $rooms = Room::query()->orderBy('name')->get(['id', 'name']);
        if ($roomId && !$rooms->pluck('id')->contains((int) $roomId))
            $roomId = null;

        // Lezioni del GIORNO selezionato (oggi di default)
        $lessonsQuery = Lesson::query()
            ->visibleTo($user)
            ->onDay($selectedDay)
            ->inRoom($roomId)
            ->with(['room', 'operator'])
            ->withCount('clients')
            ->orderBy('starts_at');

        // Label UI
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

        $lessons = $lessonsQuery->get();

        return view('calendar.index', [
            'mode' => $mode,
            'monthIso' => $monthIso,
            'monthLabel' => $monthLabel,
            'selectedDay' => $selectedIso,
            'weekStart' => $weekStartIso,  // <- nuovo
            'weekDays' => $weekDays,      // <- array di Carbon
            'roomId' => $roomId,
            'rooms' => $rooms,
            'lessons' => $lessons,
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
