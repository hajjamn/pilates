<?php

namespace App\Http\Controllers\Operator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */



    public function __construct()
    {
        //dd(app()->get(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareAliases());

        $this->middleware('role:operatore|admin');
    }

    public function index()
    {
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
        return view('operator.operators.show', compact('operator'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $operator)
    {
        return view('operator.operators.edit', compact('operator'));
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
}
