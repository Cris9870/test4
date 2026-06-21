@extends('layouts.app')

@section('title', 'Mi cuenta · ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-extrabold text-tinta">Hola, {{ $user->name }}</h1>
                <p class="mt-1 text-sm text-tinta-suave">{{ $user->email }}@if ($user->es_admin) · <span class="font-semibold text-verde">administrador</span>@endif</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('publicar') }}" class="font-display rounded-lg bg-verde px-4 py-2 text-sm font-extrabold text-white hover:bg-verde-hover">+ Publicar búsqueda</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="rounded-lg border border-borde bg-white px-4 py-2 text-sm font-semibold text-tinta hover:bg-crema">Cerrar sesión</button>
                </form>
            </div>
        </div>

        {{-- Mis búsquedas --}}
        <h2 class="font-display mt-8 text-xl font-extrabold text-tinta">Mis búsquedas publicadas</h2>
        @if ($misAnuncios->isEmpty())
            <p class="mt-2 text-sm text-tinta-suave">Aún no has publicado ninguna búsqueda. <a href="{{ route('publicar') }}" class="font-semibold text-verde hover:underline">Publica la primera</a>.</p>
        @else
            <div class="mt-3 overflow-hidden rounded-xl border border-borde bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-crema text-left text-tinta-suave"><tr>
                        <th class="px-4 py-2 font-semibold">Búsqueda</th><th class="px-4 py-2 font-semibold">Presupuesto</th>
                        <th class="px-4 py-2 font-semibold">Ofertas</th><th class="px-4 py-2 font-semibold">Estado</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($misAnuncios as $a)
                        <tr class="border-t border-borde">
                            <td class="px-4 py-2"><a href="{{ route('anuncio.show', $a->id) }}" class="font-semibold text-verde hover:underline">{{ $a->titulo }}</a></td>
                            <td class="px-4 py-2">S/ {{ number_format((float) $a->presupuesto, 2) }}</td>
                            <td class="px-4 py-2">{{ $a->ofertas_count }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-bold uppercase {{ $a->estado === 'abierto' ? 'bg-verde-tinte text-verde-hover' : 'bg-slate-200 text-slate-600' }}">{{ $a->estado }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Mis ofertas --}}
        <h2 class="font-display mt-8 text-xl font-extrabold text-tinta">Mis ofertas enviadas</h2>
        @if ($misOfertas->isEmpty())
            <p class="mt-2 text-sm text-tinta-suave">Aún no has hecho ofertas. Explora <a href="{{ route('home') }}" class="font-semibold text-verde hover:underline">lo que la gente busca</a>.</p>
        @else
            <div class="mt-3 overflow-hidden rounded-xl border border-borde bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-crema text-left text-tinta-suave"><tr>
                        <th class="px-4 py-2 font-semibold">A la búsqueda</th><th class="px-4 py-2 font-semibold">Tu precio</th><th class="px-4 py-2 font-semibold">Estado</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($misOfertas as $o)
                        <tr class="border-t border-borde">
                            <td class="px-4 py-2">
                                @if ($o->anuncio)
                                    <a href="{{ route('anuncio.show', $o->anuncio->id) }}" class="font-semibold text-verde hover:underline">{{ $o->anuncio->titulo }}</a>
                                @else <span class="text-tinta-suave">(eliminada)</span>@endif
                            </td>
                            <td class="px-4 py-2">S/ {{ number_format((float) $o->precio, 2) }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-bold uppercase {{ $o->estado === 'aceptada' ? 'bg-verde text-white' : ($o->estado === 'rechazada' ? 'bg-slate-200 text-slate-600' : 'bg-ambar/20 text-amber-800') }}">{{ $o->estado }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
