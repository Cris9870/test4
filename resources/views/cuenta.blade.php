@extends('layouts.app')

@section('title', 'Mi cuenta · ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        @if (session('ok'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">✅ {{ session('ok') }}</div>
        @endif

        <h1 class="text-2xl font-bold text-slate-900">Mi cuenta</h1>
        <p class="mt-1 text-sm text-slate-500">Ruta protegida por el middleware <code>auth</code> (sesión en Plesk).</p>

        <dl class="mt-5 rounded-xl border border-slate-200 bg-white p-5 text-sm">
            <div class="flex justify-between border-b border-slate-100 py-2">
                <dt class="text-slate-400">Nombre</dt><dd class="font-medium text-slate-800">{{ $user->name }}</dd>
            </div>
            <div class="flex justify-between border-b border-slate-100 py-2">
                <dt class="text-slate-400">Email</dt><dd class="font-medium text-slate-800">{{ $user->email }}</dd>
            </div>
            <div class="flex justify-between py-2">
                <dt class="text-slate-400">ID</dt><dd class="font-mono text-slate-600">#{{ $user->id }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Cerrar sesión</button>
        </form>
    </div>
@endsection
