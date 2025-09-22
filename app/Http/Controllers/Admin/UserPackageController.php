<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPackageController extends Controller
{
    public function store(Request $request, User $user)
    {
        $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'lessons_remaining' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'purchased_at' => ['nullable', 'date'],
        ]);

        $pkg = Package::findOrFail($request->integer('package_id'));
        $remaining = $request->filled('lessons_remaining')
            ? $request->integer('lessons_remaining')
            : (int) $pkg->total_lessons;

        UserPackage::create([
            'user_id' => $user->id,
            'package_id' => $pkg->id,
            'lessons_remaining' => $remaining,
            'purchased_at' => $request->input('purchased_at') ?: now(),
        ]);

        return back()->with('status', 'Pacchetto aggiunto a ' . $user->email);
    }

    public function edit(UserPackage $userPackage)
    {
        // piccola pagina di edit (opzionale: puoi non usarla se fai inline-edit)
        return view('admin.user_packages.edit', [
            'userPackage' => $userPackage->load(['user:id,first_name,last_name,email', 'package:id,name,total_lessons']),
        ]);
    }

    public function update(Request $request, UserPackage $userPackage)
    {
        $data = $request->validate([
            'lessons_remaining' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $userPackage->update(['lessons_remaining' => (int) $data['lessons_remaining']]);

        return redirect()
            ->route('admin.users.show', $userPackage->user_id)
            ->with('status', 'Lezioni rimanenti aggiornate.');
    }
}
