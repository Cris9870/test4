@extends('layouts.app')

@section('title', 'Iniciar sesión · ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold text-slate-900">Iniciar sesión</h1>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Contraseña</label>
                <input type="password" name="password" required
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Recordarme
            </label>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Entrar</button>
        </form>

        <p class="mt-4 text-center text-sm text-slate-500">
            ¿No tienes cuenta? <a href="{{ route('registro') }}" class="font-medium text-indigo-600 hover:underline">Regístrate</a>
        </p>
    </div>
@endsection
