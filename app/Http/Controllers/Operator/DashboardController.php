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

        // lezione corrente = la prima iniziata da poco e non ancora passata
        $currentLesson = Lesson::visibleTo($operator)
            ->whereDate('starts_at', $today)
            ->whereTime('starts_at', '<=', now())
            ->whereTime('starts_at', '>=', now()->copy()->subHours(2)) // margine, es: 2h prima
            ->with(['room', 'clients'])
            ->orderBy('starts_at')
            ->first();

        // lezioni future di oggi
        $futureLessons = Lesson::visibleTo($operator)
            ->whereDate('starts_at', $today)
            ->where('starts_at', '>', now())
            ->with(['room', 'clients'])
            ->orderBy('starts_at')
            ->get();

        // lezioni passate di oggi
        $pastLessons = Lesson::visibleTo($operator)
            ->whereDate('starts_at', $today)
            ->where('starts_at', '<', now())
            ->with(['room', 'clients'])
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
