{{-- Panel de salud de servicios. Cada chequeo viene en su try/catch desde el controlador. --}}
<div class="grid gap-3 sm:grid-cols-3 mb-6">
    @php($pg = $estado['pg'])
    <div class="rounded-xl border p-3 flex items-center gap-3 {{ $pg['ok'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
        <span class="h-2.5 w-2.5 rounded-full {{ $pg['ok'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
        <div class="min-w-0">
            <div class="text-sm font-semibold">
                PostgreSQL
                <span class="{{ $pg['ok'] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $pg['ok'] ? 'OK' : 'FALLO' }}</span>
            </div>
            <div class="text-xs text-slate-500 truncate" title="{{ $pg['detalle'] }}">{{ $pg['detalle'] }}</div>
        </div>
    </div>

    @php($meili = $estado['meili'])
    <div class="rounded-xl border p-3 flex items-center gap-3 {{ $meili['ok'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
        <span class="h-2.5 w-2.5 rounded-full {{ $meili['ok'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
        <div class="min-w-0">
            <div class="text-sm font-semibold">
                Meilisearch
                <span class="{{ $meili['ok'] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $meili['ok'] ? 'OK' : 'FALLO' }}</span>
            </div>
            <div class="text-xs text-slate-500 truncate" title="{{ $meili['detalle'] }}">{{ $meili['detalle'] }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-3 flex items-center gap-3">
        <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
        <div class="min-w-0">
            <div class="text-sm font-semibold">Servidor</div>
            <div class="text-xs text-slate-500">{{ $estado['timestamp'] }} <span class="text-slate-400">({{ $estado['tz'] }})</span></div>
        </div>
    </div>
</div>
