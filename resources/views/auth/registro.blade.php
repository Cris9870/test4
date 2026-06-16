@extends('layouts.app')

@section('title', 'Crear cuenta · ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold text-slate-900">Crear cuenta</h1>
        <p class="mt-1 text-sm text-slate-500">Auth a medida (primitivas de Laravel, sin paquetes).</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('registro') }}" class="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Nombre</label>
                <input name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Contraseña</label>
                <input type="password" name="password" required
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Repetir contraseña</label>
                <input type="password" name="password_confirmation" required
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Crear cuenta</button>
        </form>

        <p class="mt-4 text-center text-sm text-slate-500">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:underline">Inicia sesión</a>
        </p>
    </div>
@endsection
