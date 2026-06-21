@extends('admin.layout')

@section('admin')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 overflow-hidden rounded-xl border border-borde bg-white">
            <table class="w-full text-sm">
                <thead class="bg-crema text-left text-tinta-suave"><tr>
                    <th class="px-4 py-2 font-semibold">Categoría</th><th class="px-4 py-2 font-semibold">Slug</th>
                    <th class="px-4 py-2 font-semibold">Anuncios</th><th class="px-4 py-2 font-semibold">Acción</th>
                </tr></thead>
                <tbody>
                @forelse ($categorias as $c)
                    <tr class="border-t border-borde">
                        <td class="px-4 py-2 font-semibold text-tinta">{{ $c->icono }} {{ $c->nombre }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-tinta-suave">{{ $c->slug }}</td>
                        <td class="px-4 py-2">{{ $c->anuncios_count }}</td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('admin.categorias.eliminar', $c->id) }}" onsubmit="return confirm('¿Eliminar esta categoría?')">@csrf @method('DELETE')
                                <button class="rounded-md bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-700">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-tinta-suave">No hay categorías.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-borde bg-white p-5">
            <h2 class="font-display text-lg font-extrabold text-tinta">Nueva categoría</h2>
            @if ($errors->any())
                <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-2 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.categorias.guardar') }}" class="mt-3 space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-tinta">Nombre</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-tinta">Icono (emoji, opcional)</label>
                    <input name="icono" value="{{ old('icono') }}" maxlength="16" class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
                </div>
                <button class="font-display w-full rounded-lg bg-verde px-4 py-2 font-extrabold text-white hover:bg-verde-hover">Crear</button>
            </form>
        </div>
    </div>
@endsection
