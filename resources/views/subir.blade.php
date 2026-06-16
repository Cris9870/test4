@extends('layouts.app')

@section('title', 'Subir imagen · ' . config('app.name'))

@section('content')
    <a href="{{ route('home') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
        ← Volver al catalogo
    </a>

    <h1 class="text-2xl font-bold text-slate-900">Prueba de subida de imagenes</h1>
    <p class="mt-1 text-sm text-slate-500">Valida uploads al disco <code>public</code> + symlink <code>public/storage</code>.</p>

    @if (session('ok'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 break-all">
            ✅ {{ session('ok') }}
        </div>
    @endif
    @error('imagen')
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">⚠ {{ $message }}</div>
    @enderror

    {{-- Formulario con preview por Alpine (cliente, sin tocar el servidor) --}}
    <form method="POST" action="{{ route('subir') }}" enctype="multipart/form-data"
          class="mt-5 rounded-xl border border-slate-200 bg-white p-4"
          x-data="{ preview: null }">
        @csrf
        <input type="file" name="imagen" accept="image/*" required
               @change="preview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null"
               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-white hover:file:bg-indigo-700">

        <template x-if="preview">
            <img :src="preview" alt="preview"
                 class="mt-3 h-40 w-40 rounded-lg border border-slate-200 object-cover">
        </template>

        <button type="submit"
                class="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            Subir imagen
        </button>
    </form>

    <h2 class="mt-8 text-sm font-semibold uppercase tracking-wide text-slate-500">Imagenes subidas</h2>
    @if ($imagenes->isEmpty())
        <p class="mt-2 text-sm text-slate-400">Aun no hay imagenes subidas.</p>
    @else
        <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
            @foreach ($imagenes as $url)
                <a href="{{ $url }}" target="_blank"
                   class="block overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                    <img src="{{ $url }}" alt="" loading="lazy" class="aspect-square w-full object-cover">
                </a>
            @endforeach
        </div>
    @endif
@endsection
