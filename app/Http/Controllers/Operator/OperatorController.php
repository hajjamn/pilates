<?php

namespace App\Http\Controllers\Operator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StoreOperatorRequest;
use App\Http\Requests\Operator\UpdateOperatorRequest;
use App\Models\User;
use Carbon\Carbon;
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
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Accesso negato.');
        }

        return view('operator.operators.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOperatorRequest $request)
    {
        $data = $request->validated();

        $operator = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'password' => bcrypt($data['password']),
        ]);

        if (!$operator->hasVerifiedEmail()) {
            $operator->markEmailAsVerified();
        }

        $operator->assignRole('operatore');

        return redirect()
            ->route('operator.operators.show', $operator)
            ->with('status', 'Operatore creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $operator)
    {
        $this->isSelfOrAdmin($operator);

        $futureLessonsQuery = $operator->operatedLessons()->with('room')->future()->orderBy('starts_at', 'asc');
        $activeFutureLessons = $futureLessonsQuery->clone()->active()->get();
        $canceledFutureLessons = $futureLessonsQuery->clone()->canceled()->get();

        $pastLessonsQuery = $operator->operatedLessons()->with('room')->past()->orderBy('starts_at', 'desc');
        $activePastLessons = $pastLessonsQuery->clone()->active()->get();
        $canceledPastLessons = $pastLessonsQuery->clone()->canceled()->get();

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
    public function update(UpdateOperatorRequest $request, User $operator)
    {
        $data = $request->validated();

        $data['birth_date'] = !empty($data['birth_date'])
            ? Carbon::parse($data['birth_date'])
            : null;

        $operator->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'],
        ])->save();

        if ($request->has('roles')) {
            $validRoleNames = Role::pluck('name')->toArray();
            $rolesToSync = array_intersect($request->input('roles', []), $validRoleNames);
            $operator->syncRoles($rolesToSync);
        }

        return redirect()->route('operator.operators.show', $operator)->with('status', 'Operatore aggiornato con successo.');
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
