@extends('layouts.app')

@section('title', 'Busca: ' . $anuncio->titulo . ' · ' . config('app.name'))

@section('content')
    @php
        $img = $anuncio->imagen_url ?: 'https://picsum.photos/seed/anuncio' . $anuncio->id . '/800/600';
        $abierto = $anuncio->estado === 'abierto';
        $esComprador = auth()->check() && auth()->id() === $anuncio->user_id;
    @endphp

    <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-verde hover:underline mb-4">← Volver a las búsquedas</a>

    <div class="grid gap-6 md:grid-cols-2">
        {{-- Imagen --}}
        <div class="overflow-hidden rounded-2xl border border-borde bg-swatch">
            <img src="{{ $img }}" alt="{{ $anuncio->titulo }}" class="w-full object-cover" style="max-height:420px">
        </div>

        {{-- Info --}}
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-verde">{{ $anuncio->categoria }}</span>
                @if (! $abierto)
                    <span class="rounded-md bg-tinta px-2 py-0.5 text-[11px] font-bold uppercase text-white">Cerrado</span>
                @else
                    <span class="rounded-md bg-verde-tinte px-2 py-0.5 text-[11px] font-bold uppercase text-verde-hover">Abierto</span>
                @endif
            </div>
            <h1 class="font-display mt-2 text-3xl font-extrabold leading-tight text-tinta">
                <span class="text-verde-hover">Busca:</span> {{ $anuncio->titulo }}
            </h1>

            <div class="mt-4 inline-flex flex-col rounded-xl bg-ambar px-5 py-3 text-tinta">
                <span class="text-[11px] font-bold uppercase tracking-wide opacity-80">Ofrece pagar</span>
                <span class="font-display text-3xl font-black">S/ {{ number_format((float) $anuncio->presupuesto, 2) }}</span>
            </div>

            <p class="mt-5 whitespace-pre-line text-[15px] leading-relaxed text-tinta-suave">{{ $anuncio->descripcion }}</p>

            <dl class="mt-6 space-y-2 border-t border-borde pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-tinta-suave">Publicado por</dt><dd class="font-semibold text-tinta">{{ $anuncio->comprador->name }}</dd></div>
                @if ($anuncio->ciudad)
                    <div class="flex justify-between"><dt class="text-tinta-suave">Ciudad</dt><dd class="font-semibold text-tinta">📍 {{ $anuncio->ciudad }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-tinta-suave">Ofertas recibidas</dt><dd class="font-semibold text-tinta">{{ $anuncio->ofertas_count }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Formulario para ofertar / mensaje según quién mira --}}
    <div class="mt-10">
        @if (! $abierto)
            <div class="rounded-xl border border-borde bg-white p-5 text-sm text-tinta-suave">Esta búsqueda ya fue <b>cerrada</b> por el comprador.</div>
        @elseif (! auth()->check())
            <div class="rounded-xl border border-borde bg-white p-5 text-sm">
                ¿Tienes esto guardado en casa? <a href="{{ route('login') }}" class="font-semibold text-verde hover:underline">Inicia sesión</a> o
                <a href="{{ route('registro') }}" class="font-semibold text-verde hover:underline">crea una cuenta</a> para hacer tu oferta.
            </div>
        @elseif ($esComprador)
            <div class="rounded-xl border border-borde bg-verde-tinte p-5 text-sm text-verde-hover">Esta es tu búsqueda. Abajo verás las ofertas que recibas y podrás aceptar una.</div>
        @else
            <div class="rounded-2xl border border-borde bg-white p-6" x-data="{ preview: null }">
                <h2 class="font-display text-xl font-extrabold text-tinta">Hacer una oferta</h2>
                <p class="mt-1 text-sm text-tinta-suave">El comprador ya puso el precio (S/ {{ number_format((float) $anuncio->presupuesto, 2) }}). Propón el tuyo y cuéntale del producto.</p>

                @if ($errors->any())
                    <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('oferta.store', $anuncio->id) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-tinta">Tu precio (S/)</label>
                        <input type="number" name="precio" min="1" step="0.01" value="{{ old('precio') }}" required
                               class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-tinta">Mensaje (opcional)</label>
                        <textarea name="mensaje" rows="3" placeholder="Estado, marca, hace cuánto lo tienes…"
                                  class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">{{ old('mensaje') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-tinta">Foto del producto (opcional)</label>
                        <input type="file" name="imagen" accept="image/*"
                               @change="preview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null"
                               class="mt-1 block w-full text-sm text-tinta-suave file:mr-3 file:rounded-lg file:border-0 file:bg-verde file:px-4 file:py-2 file:text-white">
                        <template x-if="preview"><img :src="preview" class="mt-3 h-32 w-32 rounded-lg border border-borde object-cover"></template>
                    </div>
                    <button class="font-display rounded-lg bg-verde px-5 py-2.5 font-extrabold text-white hover:bg-verde-hover">Enviar oferta</button>
                </form>
            </div>
        @endif
    </div>

    {{-- Lista de ofertas --}}
    <div class="mt-10">
        <h2 class="font-display text-2xl font-extrabold text-tinta">Ofertas ({{ $anuncio->ofertas_count }})</h2>
        @if ($anuncio->ofertas->isEmpty())
            <p class="mt-3 text-sm text-tinta-suave">Todavía no hay ofertas. ¡Sé el primero!</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($anuncio->ofertas as $oferta)
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-borde bg-white p-4">
                        @if ($oferta->imagen_url)
                            <img src="{{ $oferta->imagen_url }}" alt="" class="h-16 w-16 rounded-lg border border-borde object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-tinta">{{ $oferta->vendedor->name }}</span>
                                @if ($oferta->estado === 'aceptada')
                                    <span class="rounded-md bg-verde px-2 py-0.5 text-[11px] font-bold uppercase text-white">Aceptada</span>
                                @elseif ($oferta->estado === 'rechazada')
                                    <span class="rounded-md bg-slate-200 px-2 py-0.5 text-[11px] font-bold uppercase text-slate-600">Rechazada</span>
                                @endif
                            </div>
                            @if ($oferta->mensaje)<p class="mt-0.5 text-sm text-tinta-suave">{{ $oferta->mensaje }}</p>@endif
                        </div>
                        <div class="font-display text-xl font-black text-verde">S/ {{ number_format((float) $oferta->precio, 2) }}</div>
                        @if ($esComprador && $abierto && $oferta->estado === 'pendiente')
                            <form method="POST" action="{{ route('oferta.aceptar', $oferta->id) }}">
                                @csrf
                                <button class="rounded-lg bg-ambar px-4 py-2 text-sm font-extrabold text-tinta hover:brightness-95">Aceptar</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
