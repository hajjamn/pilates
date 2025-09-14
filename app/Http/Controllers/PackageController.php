<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::orderBy('name')->paginate(20);
        return view('packages.index', compact('packages'));
    }

    public function show(Package $package)
    {
        return view('packages.show', compact('package'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name',
            'total_lessons' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        Package::create($validated);

        return redirect()->route('packages.index')->with('success', 'Pacchetto creato con successo.');
    }

    public function edit(Package $package)
    {
        $this->authorizeAdmin();
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:packages,name,' . $package->id,
            'total_lessons' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $package->update($validated);

        return redirect()->route('packages.show', $package)->with('success', 'Pacchetto aggiornato con successo.');
    }

    public function destroy(Package $package)
    {
        $this->authorizeAdmin();
        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Pacchetto eliminato con successo.');
    }

    private function authorizeAdmin()
    {
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Accesso negato.');
        }
    }
}
