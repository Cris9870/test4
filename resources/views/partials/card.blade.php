{{-- Tarjeta de producto. $p puede ser un modelo Eloquent o un hit de Meili (ambos soportan acceso por clave). --}}
<a href="{{ route('producto.show', ['id' => $p['id']]) }}"
   class="group block overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:shadow-md hover:-translate-y-0.5">
    <div class="aspect-[4/3] overflow-hidden bg-slate-100">
        <img src="{{ $p['imagen_url'] }}" alt="{{ $p['nombre'] }}" loading="lazy"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
    </div>
    <div class="p-3">
        <span class="inline-block rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">{{ $p['categoria'] }}</span>
        <h3 class="mt-1.5 line-clamp-2 text-sm font-semibold text-slate-800">{{ $p['nombre'] }}</h3>
        <p class="mt-1 text-base font-bold text-slate-900">${{ number_format((float) $p['precio'], 2) }}</p>
    </div>
</a>
