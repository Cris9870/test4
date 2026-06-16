@extends('layouts.app')

@section('title', 'Catalogo · ' . config('app.name'))

@section('content')
    @include('partials.estado')

    {{-- Barra superior de busqueda (htmx). No es un <form>: evitamos recargas al pulsar Enter. --}}
    <div class="mb-6">
        <div class="relative">
            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
            <input id="q" name="q" type="search" autocomplete="off"
                   value="{{ $q }}"
                   placeholder="Busca productos… prueba 'ipone', 'labtop', 'cafetera'"
                   hx-get="{{ route('buscar') }}"
                   hx-target="#resultados"
                   hx-swap="innerHTML"
                   hx-trigger="input changed delay:300ms, keyup[key=='Enter'], search"
                   hx-include="#categoria-actual"
                   hx-indicator="#spinner"
                   class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-28 text-slate-800 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            <span id="spinner" class="htmx-indicator absolute right-4 top-1/2 -translate-y-1/2 text-sm text-indigo-500">
                buscando…
            </span>
        </div>
        <p class="mt-2 text-xs text-slate-400">
            La busqueda y el filtro por categoria se actualizan via htmx (sin recargar) usando Scout → Meilisearch.
        </p>
    </div>

    {{-- Contenedor que htmx reemplaza. En el primer paint lo rinde el servidor (SSR). --}}
    <div id="resultados">
        @include('partials.resultados')
    </div>
@endsection
