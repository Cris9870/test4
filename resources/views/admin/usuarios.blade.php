@extends('admin.layout')

@section('admin')
    <div class="overflow-hidden rounded-xl border border-borde bg-white">
        <table class="w-full text-sm">
            <thead class="bg-crema text-left text-tinta-suave"><tr>
                <th class="px-4 py-2 font-semibold">Nombre</th><th class="px-4 py-2 font-semibold">Email</th>
                <th class="px-4 py-2 font-semibold">Anuncios</th><th class="px-4 py-2 font-semibold">Ofertas</th><th class="px-4 py-2 font-semibold">Rol</th>
            </tr></thead>
            <tbody>
            @forelse ($usuarios as $u)
                <tr class="border-t border-borde">
                    <td class="px-4 py-2 font-semibold text-tinta">{{ $u->name }}</td>
                    <td class="px-4 py-2">{{ $u->email }}</td>
                    <td class="px-4 py-2">{{ $u->anuncios_count }}</td>
                    <td class="px-4 py-2">{{ $u->ofertas_count }}</td>
                    <td class="px-4 py-2">
                        @if ($u->es_admin)
                            <span class="rounded-md bg-verde px-2 py-0.5 text-[11px] font-bold uppercase text-white">Admin</span>
                        @else
                            <span class="text-tinta-suave">usuario</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-tinta-suave">No hay usuarios.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $usuarios->links() }}</div>
@endsection
