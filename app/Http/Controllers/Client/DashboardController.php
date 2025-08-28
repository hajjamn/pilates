<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lesson;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $nextLesson = $user->lessonsAsClient()
            ->with(['operator', 'room'])
            ->active()
            ->future()
            ->orderBy('starts_at')
            ->first();

        return view('client.dashboard', compact('user', 'nextLesson'));
    }
}
