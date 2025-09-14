<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::all();
        return view('rooms.index', compact('rooms'));
    }

    public function show(Room $room)
    {
        return view('rooms.show', compact('room'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|unique:rooms,name',
            'description' => 'nullable|string',
            'max_clients' => ['required', 'integer', 'min:1', 'max:100']
        ]);

        Room::create($validated);

        return redirect()->route('rooms.index')->with('success', 'Sala creata con successo.');
    }

    public function edit(Room $room)
    {
        $this->authorizeAdmin();
        return view('rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|unique:rooms,name,' . $room->id,
            'description' => 'nullable|string',
            'max_clients' => ['required', 'integer', 'min:1', 'max:100']
        ]);

        $room->update($validated);

        return redirect()->route('rooms.index')->with('success', 'Sala aggiornata con successo.');
    }

    public function destroy(Room $room)
    {
        $this->authorizeAdmin();
        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Sala eliminata con successo.');
    }

    private function authorizeAdmin()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Accesso negato.');
        }
    }
}
