@extends('admin.layout')

@section('admin')
    <div class="overflow-hidden rounded-xl border border-borde bg-white">
        <table class="w-full text-sm">
            <thead class="bg-crema text-left text-tinta-suave"><tr>
                <th class="px-4 py-2 font-semibold">Búsqueda</th><th class="px-4 py-2 font-semibold">Comprador</th>
                <th class="px-4 py-2 font-semibold">Presup.</th><th class="px-4 py-2 font-semibold">Ofertas</th>
                <th class="px-4 py-2 font-semibold">Estado</th><th class="px-4 py-2 font-semibold">Acciones</th>
            </tr></thead>
            <tbody>
            @forelse ($anuncios as $a)
                <tr class="border-t border-borde">
                    <td class="px-4 py-2"><a href="{{ route('anuncio.show', $a->id) }}" class="font-semibold text-verde hover:underline">{{ $a->titulo }}</a></td>
                    <td class="px-4 py-2">{{ $a->comprador->name ?? '—' }}</td>
                    <td class="px-4 py-2">S/ {{ number_format((float) $a->presupuesto, 0) }}</td>
                    <td class="px-4 py-2">{{ $a->ofertas_count }}</td>
                    <td class="px-4 py-2">
                        <span class="rounded-md px-2 py-0.5 text-[11px] font-bold uppercase {{ $a->estado === 'abierto' ? 'bg-verde-tinte text-verde-hover' : 'bg-slate-200 text-slate-600' }}">{{ $a->estado }}</span>
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex gap-2">
                            @if ($a->estado === 'abierto')
                                <form method="POST" action="{{ route('admin.anuncios.cerrar', $a->id) }}">@csrf
                                    <button class="rounded-md border border-borde px-2 py-1 text-xs font-semibold hover:bg-crema">Cerrar</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.anuncios.eliminar', $a->id) }}" onsubmit="return confirm('¿Eliminar este anuncio?')">@csrf @method('DELETE')
                                <button class="rounded-md bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-700">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-tinta-suave">No hay anuncios.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $anuncios->links() }}</div>
@endsection
