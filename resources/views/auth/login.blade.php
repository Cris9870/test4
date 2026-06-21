@extends('layouts.app')

@section('title', 'Iniciar sesión · ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="font-display text-3xl font-extrabold text-tinta">Iniciar sesión</h1>
        <p class="mt-1 text-sm text-tinta-suave">Entra para publicar búsquedas y hacer ofertas.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4 rounded-2xl border border-borde bg-white p-6">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-tinta">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <div>
                <label class="block text-sm font-semibold text-tinta">Contraseña</label>
                <input type="password" name="password" required
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <label class="flex items-center gap-2 text-sm text-tinta-suave">
                <input type="checkbox" name="remember" value="1" class="rounded border-borde"> Recordarme
            </label>
            <button class="font-display w-full rounded-lg bg-verde px-4 py-2.5 font-extrabold text-white hover:bg-verde-hover">Entrar</button>
        </form>

        <p class="mt-4 text-center text-sm text-tinta-suave">
            ¿No tienes cuenta? <a href="{{ route('registro') }}" class="font-semibold text-verde hover:underline">Regístrate</a>
        </p>
    </div>
@endsection
