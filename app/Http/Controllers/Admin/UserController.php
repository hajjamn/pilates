<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::role('cliente')
            ->when($q !== '', function ($qq) use ($q) {
                $like = '%' . str_replace(' ', '%', $q) . '%';
                $qq->where(function ($w) use ($like) {
                    $w->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                    // se hai phone:
                    if (\Schema::hasColumn('users', 'phone')) {
                        $w->orWhere('phone', 'like', $like);
                    }
                });
            })
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q'));
    }

    public function show(User $user)
    {
        // --- Pacchetti: split Attivi vs Utilizzati ---
        // Assunzione: scope active() su UserPackage => pacchetti con lessons_remaining > 0 (o simile)
        $packagesActive = $user->packages()
            ->active()
            ->with('package:id,name,total_lessons')
            ->orderByDesc('purchased_at')
            ->get();

        // "Utilizzati": non attivi. Se non hai uno scope, filtriamo per lessons_remaining <= 0
        $packagesUsed = $user->packages()
            ->where(function ($q) {
                $q->where('lessons_remaining', '<=', 0)->orWhereNull('lessons_remaining');
            })
            ->with('package:id,name,total_lessons')
            ->orderByDesc('purchased_at')
            ->get();

        // --- Lezioni del cliente (come deciso) ---
        $rawLessons = $user->lessonsAsClient()
            ->with(['room:id,name'])
            ->orderByDesc('starts_at')
            ->get();

        $lessons = [
            'future' => [
                'active' => $rawLessons->filter(fn($l) => !$l->canceled && $l->starts_at?->isFuture())->values(),
                'canceled' => $rawLessons->filter(fn($l) => $l->canceled && $l->starts_at?->isFuture())->values(),
            ],
            'past' => [
                'active' => $rawLessons->filter(fn($l) => !$l->canceled && $l->starts_at?->isPast())->values(),
                'canceled' => $rawLessons->filter(fn($l) => $l->canceled && $l->starts_at?->isPast())->values(),
            ],
        ];

        return view('admin.users.show', compact('user', 'packagesActive', 'packagesUsed', 'lessons'));
    }


}