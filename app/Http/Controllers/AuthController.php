<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Auth "a medida" con las primitivas del core de Laravel (sin Breeze/Jetstream).
 * Demuestra que NO hace falta un paquete: Auth::attempt/login/logout + sesiones.
 */
class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.registro');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // El cast 'hashed' del modelo User hashea la contraseña al asignarla.
        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('cuenta')->with('ok', "Cuenta creada. ¡Bienvenido, {$user->name}!");
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Esas credenciales no coinciden.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('cuenta'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function cuenta(Request $request)
    {
        return view('cuenta', ['user' => $request->user()]);
    }
}
