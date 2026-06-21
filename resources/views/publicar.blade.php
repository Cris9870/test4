@extends('layouts.app')

@section('title', 'Publicar una búsqueda · ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="font-display text-3xl font-extrabold text-tinta">Publica lo que quieres comprar</h1>
        <p class="mt-1 text-sm text-tinta-suave">Di qué buscas y cuánto ofreces. Quien lo tenga guardado en casa te hará una oferta.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                <ul class="list-disc pl-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('publicar') }}" enctype="multipart/form-data"
              class="mt-6 space-y-5 rounded-2xl border border-borde bg-white p-6" x-data="{ preview: null }">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-tinta">¿Qué buscas?</label>
                <input type="text" name="titulo" value="{{ old('titulo') }}" required maxlength="160"
                       placeholder="Ej: un sofá de 2 cuerpos en buen estado"
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <div>
                <label class="block text-sm font-semibold text-tinta">Descripción</label>
                <textarea name="descripcion" rows="4" required maxlength="2000"
                          placeholder="Cuenta detalles: color, tamaño, estado que buscas, para cuándo lo necesitas…"
                          class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">{{ old('descripcion') }}</textarea>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-tinta">Categoría</label>
                    <select name="categoria_id" required class="mt-1 w-full rounded-lg border border-borde bg-white px-3 py-2 outline-none focus:border-verde">
                        <option value="">Elige una…</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" @selected(old('categoria_id') == $cat->id)>{{ $cat->icono }} {{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-tinta">¿Cuánto ofreces? (S/)</label>
                    <input type="number" name="presupuesto" min="1" step="0.01" value="{{ old('presupuesto') }}" required
                           class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-tinta">Ciudad (opcional)</label>
                <input type="text" name="ciudad" value="{{ old('ciudad') }}" maxlength="80" placeholder="Ej: Lima"
                       class="mt-1 w-full rounded-lg border border-borde px-3 py-2 outline-none focus:border-verde">
            </div>
            <div>
                <label class="block text-sm font-semibold text-tinta">Imagen de referencia (opcional)</label>
                <input type="file" name="imagen" accept="image/*"
                       @change="preview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null"
                       class="mt-1 block w-full text-sm text-tinta-suave file:mr-3 file:rounded-lg file:border-0 file:bg-verde file:px-4 file:py-2 file:text-white">
                <template x-if="preview"><img :src="preview" class="mt-3 h-36 w-36 rounded-lg border border-borde object-cover"></template>
            </div>
            <button class="font-display rounded-lg bg-verde px-6 py-3 font-extrabold text-white hover:bg-verde-hover">Publicar mi búsqueda</button>
        </form>
    </div>
@endsection
