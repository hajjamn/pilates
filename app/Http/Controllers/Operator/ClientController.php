<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    public function create()
    {
        return view('operator.clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'phone:IT'],
            'birth_date' => ['nullable', 'date'],
        ]);

        // Password temporanea (poi potremo inviare "imposta password" via email)
        //$tempPassword = Str::random(16);
        $tempPassword = '1234';

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($tempPassword),
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
        ]);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified(); // imposta email_verified_at + event(Verified)
        }

        $user->assignRole('cliente');

        // In futuro: inviare notifica al cliente con link per impostare la password.

        return redirect()
            ->route('operator.dashboard')
            ->with('success', 'Cliente creato correttamente. La sua email risulta verificata.');
    }
}
