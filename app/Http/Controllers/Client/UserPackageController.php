<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UserPackage;
use App\Models\LessonUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPackageController extends Controller
{
    /**
     * Elenco storico dei pacchetti dell'utente autenticato.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $packages = $user->packages()
            ->with(['package:id,name,total_lessons'])
            ->orderByDesc('purchased_at')
            ->paginate(20);

        return view('client.user-packages.index', compact('packages'));
    }

    /**
     * Dettaglio di un singolo user_package con lezioni che lo hanno scalato.
     */
    public function show(UserPackage $userPackage, Request $request)
    {
        $user = Auth::user();
        if ((int) $userPackage->user_id !== (int) $user->id) {
            abort(403, 'Accesso negato.');
        }

        $usages = LessonUser::query()
            ->where('lesson_users.user_package_id', $userPackage->id)
            ->where('lesson_users.counted', true)
            ->whereNull('lesson_users.deleted_at')
            ->join('lessons', function ($j) {
                $j->on('lessons.id', '=', 'lesson_users.lesson_id')
                    ->whereNull('lessons.deleted_at');
            })
            ->orderByDesc('lessons.starts_at')
            ->select('lesson_users.*')
            ->with([
                'lesson' => function ($q) {
                    $q->with(['room', 'operator']);
                }
            ])
            ->paginate(20);

        $userPackage->load('package:id,name,total_lessons');

        return view('client.user-packages.show', [
            'userPackage' => $userPackage,
            'usages' => $usages,
        ]);
    }
}
