<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Room;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::with('room')->orderBy('name')->paginate(20);
        return view('machines.index', compact('machines'));
    }

    public function show(Machine $machine)
    {
        $machine->load('room');
        return view('machines.show', compact('machine'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        $rooms = Room::orderBy('name')->get();
        $selectedRoomId = request('room_id');
        return view('machines.create', compact('rooms', 'selectedRoomId'));
    }


    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_id' => 'required|exists:rooms,id',
            'description' => 'nullable|string',
        ]);

        Machine::create($validated);

        return redirect()->route('machines.index')->with('success', 'Macchina creata con successo.');
    }

    public function edit(Machine $machine)
    {
        $this->authorizeAdmin();
        $rooms = Room::orderBy('name')->get();
        return view('machines.edit', compact('machine', 'rooms'));
    }

    public function update(Request $request, Machine $machine)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_id' => 'required|exists:rooms,id',
            'description' => 'nullable|string',
        ]);

        $machine->update($validated);

        return redirect()->route('machines.show', $machine)->with('success', 'Macchina aggiornata con successo.');
    }

    public function destroy(Machine $machine)
    {
        $this->authorizeAdmin();
        $machine->delete();

        return redirect()->route('machines.index')->with('success', 'Macchina eliminata con successo.');
    }

    private function authorizeAdmin()
    {
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Accesso negato.');
        }
    }
}
