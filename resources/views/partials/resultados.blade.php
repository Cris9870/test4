{{--
  Parcial de resultados (lo devuelve /buscar y tambien lo renderiza la home en el primer paint).
  Reemplaza SOLO el contenedor #resultados via htmx, sin recargar la pagina.
--}}

{{-- Estado de la categoria activa: lo lee el input de busqueda via hx-include="#categoria-actual". --}}
<input type="hidden" id="categoria-actual" name="categoria" value="{{ $categoria }}">

{{-- Facetas por categoria (filterableAttributes de Meili). Cada chip dispara htmx incluyendo el texto (#q). --}}
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px">
    <button type="button"
            hx-get="{{ route('buscar', ['categoria' => '']) }}"
            hx-target="#resultados" hx-swap="innerHTML"
            hx-include="#q" hx-indicator="#spinner"
            class="chip {{ $categoria === '' ? 'on' : '' }}">
        Todas <span style="opacity:.7">({{ array_sum($facets) }})</span>
    </button>

    @foreach ($facets as $cat => $count)
        <button type="button"
                hx-get="{{ route('buscar', ['categoria' => $cat]) }}"
                hx-target="#resultados" hx-swap="innerHTML"
                hx-include="#q" hx-indicator="#spinner"
                class="chip {{ $categoria === $cat ? 'on' : '' }}">
            {{ $cat }} <span style="opacity:.7">({{ $count }})</span>
        </button>
    @endforeach
</div>

{{-- Resumen: total + fuente + processingTimeMs de Meili --}}
<div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:16px;font-size:13px;color:var(--tinta-suave)">
    <span style="font-weight:700;color:var(--tinta)">{{ $total }} búsqueda(s)</span>

    @if ($fuente === 'meilisearch' && $processingTimeMs !== null)
        <span style="display:inline-flex;align-items:center;gap:4px;background:var(--tinta);color:#fff;border-radius:7px;padding:2px 8px;font-size:12px;font-weight:600">
            ⚡ Meilisearch · {{ $processingTimeMs }} ms
        </span>
    @else
        <span style="display:inline-flex;align-items:center;gap:4px;background:#FCEFCF;color:#b3760a;border-radius:7px;padding:2px 8px;font-size:12px;font-weight:600">
            ⚠ respaldo PostgreSQL (Meilisearch no disponible)
        </span>
    @endif

    @if ($q !== '')
        <span>· texto: <span style="font-weight:600;color:var(--tinta)">"{{ $q }}"</span></span>
    @endif
    @if ($categoria !== '')
        <span>· categoría: <span style="font-weight:600;color:var(--tinta)">{{ $categoria }}</span></span>
    @endif
</div>

{{-- Muro masonry de resultados --}}
@if ($total === 0)
    <div style="border:1px dashed var(--borde);background:#fff;border-radius:var(--radio);padding:40px;text-align:center;color:var(--tinta-suave)">
        @if ($q !== '')
            No se encontraron búsquedas para <span style="font-weight:600">"{{ $q }}"</span>.
        @else
            Aún no hay búsquedas publicadas.
        @endif
        <div style="margin-top:6px;font-size:12px;color:#a3a799">Prueba con: sofá, bici, licuadora, laptop, televisor…</div>
    </div>
@else
    <div class="wall">
        @foreach ($items as $p)
            @include('partials.card', ['p' => $p])
        @endforeach
    </div>
@endif
