{{--
  Parcial de resultados (lo devuelve /buscar y tambien lo renderiza la home en el primer paint).
  Reemplaza SOLO el contenedor #resultados via htmx, sin recargar la pagina.
--}}

{{-- Estado de la categoria activa: lo lee el input de busqueda via hx-include="#categoria-actual". --}}
<input type="hidden" id="categoria-actual" name="categoria" value="{{ $categoria }}">

{{-- Facetas por categoria (filterableAttributes de Meili). Cada chip dispara htmx incluyendo el texto (#q). --}}
<div class="mb-4 flex flex-wrap gap-2">
    <button type="button"
            hx-get="{{ route('buscar', ['categoria' => '']) }}"
            hx-target="#resultados" hx-swap="innerHTML"
            hx-include="#q" hx-indicator="#spinner"
            class="rounded-full border px-3 py-1 text-sm transition {{ $categoria === '' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-600 hover:border-indigo-400' }}">
        Todas <span class="opacity-70">({{ array_sum($facets) }})</span>
    </button>

    @foreach ($facets as $cat => $count)
        <button type="button"
                hx-get="{{ route('buscar', ['categoria' => $cat]) }}"
                hx-target="#resultados" hx-swap="innerHTML"
                hx-include="#q" hx-indicator="#spinner"
                class="rounded-full border px-3 py-1 text-sm transition {{ $categoria === $cat ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-600 hover:border-indigo-400' }}">
            {{ $cat }} <span class="opacity-70">({{ $count }})</span>
        </button>
    @endforeach
</div>

{{-- Resumen: total + fuente + processingTimeMs de Meili --}}
<div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-500">
    <span class="font-medium text-slate-700">{{ $total }} resultado(s)</span>

    @if ($fuente === 'meilisearch' && $processingTimeMs !== null)
        <span class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-2 py-0.5 text-xs font-medium text-white">
            ⚡ Meilisearch · {{ $processingTimeMs }} ms
        </span>
    @else
        <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
            ⚠ fallback PostgreSQL (Meilisearch no disponible)
        </span>
    @endif

    @if ($q !== '')
        <span>· busqueda: <span class="font-medium text-slate-700">"{{ $q }}"</span></span>
    @endif
    @if ($categoria !== '')
        <span>· categoria: <span class="font-medium text-slate-700">{{ $categoria }}</span></span>
    @endif
</div>

{{-- Grilla de resultados --}}
@if ($total === 0)
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
        @if ($q !== '')
            No se encontraron productos para <span class="font-medium">"{{ $q }}"</span>.
        @else
            No se encontraron productos.
        @endif
        <div class="mt-1 text-xs text-slate-400">Prueba con: ipone, labtop, cafetera, libro…</div>
    </div>
@else
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($items as $p)
            @include('partials.card', ['p' => $p])
        @endforeach
    </div>
@endif
