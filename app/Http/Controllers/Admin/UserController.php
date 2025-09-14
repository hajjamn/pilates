<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        // pacchetti attivi e non
        $packages = $user->packages()
            ->with('package:id,name,total_lessons')
            ->orderByDesc('purchased_at')
            ->get();

        return view('admin.users.show', compact('user', 'packages'));
    }
}