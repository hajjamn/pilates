<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user();
        $today = Carbon::today();

        // ===== MIE LEZIONI =====
        $currentLesson = Lesson::visibleTo($admin)
            ->whereDate('starts_at', $today)
            ->where('starts_at', '<=', now()) // iniziata
            ->whereRaw("DATE_ADD(starts_at, INTERVAL 1 HOUR) >= ?", [now()]) // non ancora finita
            ->where('canceled', false)
            ->with(['room'])
            ->withCount('clients')
            ->orderBy('starts_at')
            ->first();

        $futureLessons = Lesson::visibleTo($admin)
            ->whereDate('starts_at', $today)
            ->where('starts_at', '>', now())
            ->with(['room'])
            ->withCount('clients')
            ->orderBy('starts_at')
            ->get();

        $pastLessons = Lesson::visibleTo($admin)
            ->whereDate('starts_at', $today)
            ->whereRaw("DATE_ADD(starts_at, INTERVAL 1 HOUR) < ?", [now()])
            ->with(['room'])
            ->withCount('clients')
            ->orderByDesc('starts_at')
            ->get();

        // ===== LEZIONI ALTRI OPERATORI =====
        $baseOthers = Lesson::query()
            ->whereDate('starts_at', $today)
            ->where('operator_id', '!=', $admin->id);

        $currentLessonsOthers = (clone $baseOthers)
            ->whereTime('starts_at', '<=', now())
            ->whereRaw("DATE_ADD(starts_at, INTERVAL 1 HOUR) >= ?", [now()]) // non ancora finita
            ->where('canceled', false)
            ->with(['room', 'operator'])      // utile mostrare l’operatore se vuoi
            ->withCount('clients')
            ->orderBy('starts_at')
            ->get();

        $futureLessonsOthers = (clone $baseOthers)
            ->where('starts_at', '>', now())
            ->with(['room', 'operator'])
            ->withCount('clients')
            ->orderBy('starts_at')
            ->get();

        $pastLessonsOthers = (clone $baseOthers)
            ->whereRaw("DATE_ADD(starts_at, INTERVAL 1 HOUR) < ?", [now()])
            ->with(['room', 'operator'])
            ->withCount('clients')
            ->orderByDesc('starts_at')
            ->get();

        return view('admin.dashboard', compact(
            'admin',
            'currentLesson',
            'futureLessons',
            'pastLessons',
            'currentLessonsOthers',
            'futureLessonsOthers',
            'pastLessonsOthers'
        ));
    }
}
