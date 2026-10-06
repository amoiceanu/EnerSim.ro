<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate(['email' => ['required', 'string', 'max:255'], 'password' => ['required', 'string']]);
        $identifier = trim($input['email']);
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [$field => $identifier, 'password' => $input['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Datele de autentificare nu sunt valide.'])->onlyInput('email');
        }

        if (! $request->user()?->is_admin) {
            Auth::logout();

            return back()->withErrors(['email' => 'Momentan accesul este disponibil doar pentru administrator.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
