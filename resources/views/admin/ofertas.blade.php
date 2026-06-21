@extends('admin.layout')

@section('admin')
    <div class="overflow-hidden rounded-xl border border-borde bg-white">
        <table class="w-full text-sm">
            <thead class="bg-crema text-left text-tinta-suave"><tr>
                <th class="px-4 py-2 font-semibold">A la búsqueda</th><th class="px-4 py-2 font-semibold">Vendedor</th>
                <th class="px-4 py-2 font-semibold">Precio</th><th class="px-4 py-2 font-semibold">Estado</th><th class="px-4 py-2 font-semibold">Acciones</th>
            </tr></thead>
            <tbody>
            @forelse ($ofertas as $o)
                <tr class="border-t border-borde">
                    <td class="px-4 py-2">
                        @if ($o->anuncio)
                            <a href="{{ route('anuncio.show', $o->anuncio->id) }}" class="font-semibold text-verde hover:underline">{{ $o->anuncio->titulo }}</a>
                        @else <span class="text-tinta-suave">(eliminada)</span>@endif
                    </td>
                    <td class="px-4 py-2">{{ $o->vendedor->name ?? '—' }}</td>
                    <td class="px-4 py-2">S/ {{ number_format((float) $o->precio, 2) }}</td>
                    <td class="px-4 py-2">{{ $o->estado }}</td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('admin.ofertas.eliminar', $o->id) }}" onsubmit="return confirm('¿Eliminar esta oferta?')">@csrf @method('DELETE')
                            <button class="rounded-md bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-700">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-tinta-suave">No hay ofertas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $ofertas->links() }}</div>
@endsection
