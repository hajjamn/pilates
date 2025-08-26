<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Lesson;

class LessonCalendarController extends Controller
{
    public function index(Request $request)
    {
        //app()->setLocale('it');
        //Carbon::setLocale('it');

        $dayParam = $request->query('day');
        $monthParam = $request->query('month');
        $roomId = $request->query('room_id');

        $selectedDay = $this->parseDayOrToday($dayParam);
        $contextMonth = $this->parseMonthOrFromDay($monthParam, $selectedDay); // Carbon (startOfMonth)

        $user = Auth::user();
        $mode = $user->hasRole('admin')
            ? 'admin'
            : ($user->hasRole('operatore') ? 'operator' : 'client');

        $rooms = Room::query()->orderBy('name')->get(['id', 'name']);

        if ($roomId && !$rooms->pluck('id')->contains((int) $roomId)) {
            $roomId = null;
        }

        $lessons = Lesson::query()
            ->visibleTo($user)
            ->onDay($selectedDay)
            ->inRoom($roomId)
            ->with(['room', 'operator'])
            ->withCount('clients')
            ->orderBy('starts_at')
            ->get();

        $monthIso = $contextMonth->format('Y-m');
        $monthLabel = ucfirst($contextMonth->translatedFormat('F'));
        $selectedIso = $selectedDay->toDateString();
        $weekDays = $this->getWeekDays($selectedDay);

        return view('calendar.index', [
            'mode' => $mode,
            'monthIso' => $monthIso,
            'monthLabel' => $monthLabel,
            'weekDays' => $weekDays,
            'selectedDay' => $selectedIso,
            'roomId' => $roomId,
            'rooms' => $rooms,
            'lessons' => $lessons,
        ]);
    }

    // -----------------------------
    // Helpers privati
    // -----------------------------

    /**
     * Prova a parsare 'YYYY-MM-DD', altrimenti torna oggi (startOfDay).
     */
    private function parseDayOrToday(?string $dayParam): Carbon
    {
        if ($dayParam) {
            try {
                return Carbon::createFromFormat('Y-m-d', $dayParam)->startOfDay();
            } catch (\Throwable $e) {
                // se invalido, fallback su oggi
            }
        }
        return now()->startOfDay();
    }

    /**
     * Prova a parsare 'YYYY-MM', altrimenti usa il mese della giornata passata.
     */

    private function getWeekDays(Carbon $selectedDay): array
    {
        $startOfWeek = $selectedDay->copy()->startOfWeek(Carbon::MONDAY);
        return collect()
            ->range(0, 6)
            ->map(fn($i) => $startOfWeek->copy()->addDays($i))
            ->all();
    }

    private function parseMonthOrFromDay(?string $monthParam, Carbon $day): Carbon
    {
        if ($monthParam) {
            try {
                return Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
            } catch (\Throwable $e) {
                // se invalido, fallback sotto
            }
        }
        return $day->copy()->startOfMonth();
    }
}
