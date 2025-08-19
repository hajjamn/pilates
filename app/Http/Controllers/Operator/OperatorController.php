<?php

namespace App\Http\Controllers\Operator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class OperatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index()
    {

        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Accesso negato.');
        }

        $operators = User::operators()->get();

        return view('operator.operators.index', compact('operators'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('operator.operators.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $operator)
    {

        $this->isSelfOrAdmin($operator);

        $futureLessonsQuery = $operator->operatedLessons()->future();
        $activeFutureLessons = $futureLessonsQuery->active()->get();
        $canceledFutureLessons = $futureLessonsQuery->canceled()->get();

        $pastLessonsQuery = $operator->operatedLessons()->past();
        $activePastLessons = $pastLessonsQuery->active()->get();
        $canceledPastLessons = $pastLessonsQuery->canceled()->get();

        $lessons = [
            'future' => [
                'active' => $activeFutureLessons,
                'canceled' => $canceledFutureLessons,
            ],
            'past' => [
                'active' => $activePastLessons,
                'canceled' => $canceledPastLessons,
            ],
        ];

        return view('operator.operators.show', compact(['operator', 'lessons']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $operator)
    {
        $this->isSelfOrAdmin($operator);

        $roles = auth()->user()->hasRole('admin')
            ? Role::all()
            : collect();

        return view('operator.operators.edit', compact('operator', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $operator)
    {
        $operator->delete();
        return redirect()->route('operator.operators.index')->with('success', 'Operatore eliminato con successo.');
    }

    public function isSelfOrAdmin(User $user)
    {
        if (!auth()->user()->hasRole('admin') && auth()->id() !== $user->id) {
            abort(403, 'Accesso negato.');
        }
    }
}
