{{-- Panel de salud de servicios (diagnóstico). Cada chequeo viene en su try/catch desde el controlador. --}}
@php($pg = $estado['pg'])
@php($meili = $estado['meili'])
<div style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;margin-bottom:8px;font-size:12.5px;color:var(--tinta-suave)">
    <span style="display:inline-flex;align-items:center;gap:6px">
        <span style="width:9px;height:9px;border-radius:50%;background:{{ $pg['ok'] ? '#10b981' : '#ef4444' }}"></span>
        <b style="color:var(--tinta)">PostgreSQL</b> {{ $pg['ok'] ? 'OK' : 'FALLO' }} · {{ $pg['detalle'] }}
    </span>
    <span style="display:inline-flex;align-items:center;gap:6px">
        <span style="width:9px;height:9px;border-radius:50%;background:{{ $meili['ok'] ? '#10b981' : '#ef4444' }}"></span>
        <b style="color:var(--tinta)">Meilisearch</b> {{ $meili['ok'] ? 'OK' : 'FALLO' }}
    </span>
    <span style="opacity:.7">{{ $estado['timestamp'] }} ({{ $estado['tz'] }})</span>
</div>
