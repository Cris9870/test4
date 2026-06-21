@extends('layouts.app')

@section('title', 'Crear cuenta · ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="font-display text-3xl font-extrabold text-tinta">Crear cuenta</h1>
        <p class="mt-1 text-sm text-tinta-suave">Únete a Reversa: publica lo que buscas o vende lo que ya no usas.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                <ul class="list-disc pl-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('registro') }}" class="mt-5 space-y-4 rounded-2xl border border-borde bg-white p-6">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-tinta">Nombre</label>
                <input name="name" value="{{ old('name') }}" required autofocus
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <div>
                <label class="block text-sm font-semibold text-tinta">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <div>
                <label class="block text-sm font-semibold text-tinta">Contraseña</label>
                <input type="password" name="password" required
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <div>
                <label class="block text-sm font-semibold text-tinta">Repetir contraseña</label>
                <input type="password" name="password_confirmation" required
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <button class="font-display w-full rounded-lg bg-verde px-4 py-2.5 font-extrabold text-white hover:bg-verde-hover">Crear cuenta</button>
        </form>

        <p class="mt-4 text-center text-sm text-tinta-suave">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold text-verde hover:underline">Inicia sesión</a>
        </p>
    </div>
@endsection
