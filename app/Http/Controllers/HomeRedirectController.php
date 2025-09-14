<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return view('welcome');
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('operatore')) {
            return redirect()->route('operator.dashboard');
        }

        if ($user->hasRole('cliente')) {
            return redirect()->route('client.dashboard');
        }

        abort(403, 'Accesso negato.');
    }
}
