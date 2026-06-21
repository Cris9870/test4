{{-- Tarjeta de un Anuncio (solicitud de compra). $p puede ser un modelo Eloquent o un hit de Meili (ambos por clave). --}}
@php
    $img = ($p['imagen_url'] ?? null) ?: 'https://picsum.photos/seed/anuncio' . $p['id'] . '/600/450';
    $ciudad = data_get($p, 'ciudad');
    $ofertas = data_get($p, 'ofertas_count');
    $cerrado = data_get($p, 'estado') === 'cerrado';
@endphp
<a href="{{ route('anuncio.show', ['id' => $p['id']]) }}" class="card">
    <div class="media">
        @if ($cerrado)
            <div class="badges"><span class="bdg cerrado">Cerrado</span></div>
        @endif
        <img src="{{ $img }}" alt="{{ $p['titulo'] }}" loading="lazy">
        <div class="price-tag"><small>Ofrece</small><b>S/ {{ number_format((float) $p['presupuesto'], 0) }}</b></div>
    </div>
    <div class="body">
        <div class="cat-line">
            <span class="c">{{ $p['categoria'] }}</span>
            @if ($ciudad)<span class="t">{{ $ciudad }}</span>@endif
        </div>
        <h3><b>Busca:</b> {{ $p['titulo'] }}</h3>
        <div class="meta">
            <span class="av">{{ str($p['titulo'])->substr(0, 1)->upper() }}</span>
            <div>
                <div class="who">Comprador en Reversa</div>
                @if ($ciudad)<div class="loc">📍 {{ $ciudad }}</div>@endif
            </div>
        </div>
        @if ($ofertas !== null)
            <div class="rev"><span>💬</span><span class="n">{{ $ofertas }}</span><span class="lbl">ofertas recibidas</span></div>
        @endif
        <span class="cta">Tengo este · <b style="color:var(--ambar);margin-left:5px">Hacer oferta</b></span>
    </div>
</a>
