@extends('layouts.app')

@section('title', $producto->nombre . ' · ' . config('app.name'))

@section('content')
    <a href="{{ route('home') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
        ← Volver al catalogo
    </a>

    {{-- Microinteraccion Alpine.js: galeria de miniaturas. 100% cliente, NO toca el servidor. --}}
    <div class="grid gap-8 md:grid-cols-2" x-data="{ activa: '{{ $galeria[0] }}' }">
        <div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                <img :src="activa" alt="{{ $producto->nombre }}" class="aspect-[4/3] w-full object-cover">
            </div>

            <div class="mt-3 flex gap-3">
                @foreach ($galeria as $img)
                    <button type="button" @click="activa = '{{ $img }}'"
                            :class="activa === '{{ $img }}' ? 'ring-2 ring-indigo-500 ring-offset-2' : 'opacity-70 hover:opacity-100'"
                            class="h-16 w-16 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 transition">
                        <img src="{{ $img }}" alt="" class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-400">
                Galeria interactiva con Alpine.js (cambia la imagen principal sin peticiones al servidor).
            </p>
        </div>

        <div>
            <span class="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">{{ $producto->categoria }}</span>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $producto->nombre }}</h1>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">${{ number_format((float) $producto->precio, 2) }}</p>

            <p class="mt-2 text-sm">
                @if ($producto->stock > 0)
                    <span class="font-medium text-emerald-600">● {{ $producto->stock }} en stock</span>
                @else
                    <span class="font-medium text-rose-600">● Sin stock</span>
                @endif
            </p>

            <div class="mt-5 border-t border-slate-200 pt-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Descripcion</h2>
                <p class="mt-2 leading-relaxed text-slate-700">{{ $producto->descripcion }}</p>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-2 text-sm">
                <dt class="text-slate-400">ID</dt>
                <dd class="text-right font-mono text-slate-600">#{{ $producto->id }}</dd>
                <dt class="text-slate-400">Categoria</dt>
                <dd class="text-right text-slate-600">{{ $producto->categoria }}</dd>
            </dl>
        </div>
    </div>
@endsection
