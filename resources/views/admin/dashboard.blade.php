@extends('admin.layout')

@section('admin')
    @php
        $cards = [
            ['Usuarios', $stats['usuarios'], '👤'],
            ['Anuncios', $stats['anuncios'], '📋'],
            ['Abiertos', $stats['anuncios_abiertos'], '🟢'],
            ['Ofertas', $stats['ofertas'], '💬'],
            ['Categorías', $stats['categorias'], '🏷️'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($cards as [$label, $valor, $ic])
            <div class="rounded-xl border border-borde bg-white p-4">
                <div class="text-2xl">{{ $ic }}</div>
                <div class="font-display mt-1 text-3xl font-black text-tinta">{{ number_format($valor) }}</div>
                <div class="text-sm text-tinta-suave">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    <h2 class="font-display mt-8 text-lg font-extrabold text-tinta">Últimos anuncios</h2>
    <div class="mt-3 overflow-hidden rounded-xl border border-borde bg-white">
        <table class="w-full text-sm">
            <thead class="bg-crema text-left text-tinta-suave"><tr>
                <th class="px-4 py-2 font-semibold">Búsqueda</th><th class="px-4 py-2 font-semibold">Comprador</th>
                <th class="px-4 py-2 font-semibold">Ofertas</th><th class="px-4 py-2 font-semibold">Estado</th>
            </tr></thead>
            <tbody>
            @foreach ($ultimos as $a)
                <tr class="border-t border-borde">
                    <td class="px-4 py-2"><a href="{{ route('anuncio.show', $a->id) }}" class="font-semibold text-verde hover:underline">{{ $a->titulo }}</a></td>
                    <td class="px-4 py-2">{{ $a->comprador->name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $a->ofertas_count }}</td>
                    <td class="px-4 py-2">{{ $a->estado }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
