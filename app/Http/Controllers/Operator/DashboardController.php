<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $operator = $request->user();

        // oggi
        $today = Carbon::today();

        // lezione corrente = la prima iniziata da poco e non ancora passata (finestra 2h)
        $currentLesson = Lesson::visibleTo($operator)
            ->whereDate('starts_at', $today)
            ->where('starts_at', '<=', now()) // iniziata
            ->whereRaw("DATE_ADD(starts_at, INTERVAL 1 HOUR) >= ?", [now()]) // non ancora finita
            ->where('canceled', false)
            ->with(['room'])
            ->withCount('clients')
            ->orderBy('starts_at')
            ->first();

        // lezioni future di oggi
        $futureLessons = Lesson::visibleTo($operator)
            ->whereDate('starts_at', $today)
            ->where('starts_at', '>', now())
            ->with(['room'])
            ->withCount('clients')
            ->orderBy('starts_at')
            ->get();

        // lezioni passate di oggi
        $pastLessons = Lesson::visibleTo($operator)
            ->whereDate('starts_at', $today)
            ->whereRaw("DATE_ADD(starts_at, INTERVAL 1 HOUR) < ?", [now()])
            ->with(['room'])
            ->withCount('clients')
            ->orderByDesc('starts_at')
            ->get();

        return view('operator.dashboard', compact(
            'operator',
            'currentLesson',
            'futureLessons',
            'pastLessons'
        ));
    }
}
