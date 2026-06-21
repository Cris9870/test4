@extends('layouts.app')

@section('title', 'Admin · ' . config('app.name'))

@section('content')
    @php
        $nav = [
            'admin.dashboard' => 'Resumen',
            'admin.anuncios' => 'Anuncios',
            'admin.ofertas' => 'Ofertas',
            'admin.usuarios' => 'Usuarios',
            'admin.categorias' => 'Categorías',
        ];
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-extrabold text-tinta">⚙ Panel de administración</h1>
        <span class="text-xs text-tinta-suave">Reversa · solo administradores</span>
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($nav as $route => $label)
            <a href="{{ route($route) }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs($route) ? 'bg-verde text-white' : 'border border-borde bg-white text-tinta hover:bg-crema' }}">{{ $label }}</a>
        @endforeach
    </div>

    @yield('admin')
@endsection
