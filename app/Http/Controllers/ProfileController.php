<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'phone:IT'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $emailChanged = ($data['email'] !== $user->email);

        // campi base
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,       // normalizzato dal mutator
            'birth_date' => $data['birth_date'] ?? null,
        ]);

        // cambio email ⇒ invalida verifica
        if ($emailChanged) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();

            return back()->with('success', 'Email aggiornata. Ti abbiamo inviato un nuovo link di verifica.');
        }

        return back()->with('success', 'Profilo aggiornato.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete(); // Soft delete abilitato
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home.redirect')->with('status', 'Account eliminato.');
    }
}
