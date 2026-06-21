<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnuncioController extends Controller
{
    /**
     * Detalle SSR de una solicitud de compra: muestra el anuncio, sus ofertas
     * y (según quién mira) el formulario para ofertar o los botones de aceptar.
     */
    public function show(int $id)
    {
        $anuncio = Anuncio::with(['comprador', 'categoriaRel', 'ofertas.vendedor'])
            ->withCount('ofertas')
            ->findOrFail($id);

        return view('anuncio', compact('anuncio'));
    }

    public function create()
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return view('publicar', compact('categorias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:160'],
            'descripcion' => ['required', 'string', 'max:2000'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'presupuesto' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'imagen' => ['nullable', 'image', 'max:4096'],
        ]);

        $categoria = Categoria::findOrFail($data['categoria_id']);

        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('anuncios', 'public');
            $imagenUrl = Storage::disk('public')->url($path);
        }

        $anuncio = Anuncio::create([
            'user_id' => $request->user()->id,
            'categoria_id' => $categoria->id,
            'categoria' => $categoria->nombre, // denormalizado para faceting
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'presupuesto' => $data['presupuesto'],
            'ciudad' => $data['ciudad'] ?? null,
            'imagen_url' => $imagenUrl,
            'estado' => 'abierto',
        ]);

        return redirect()->route('anuncio.show', $anuncio->id)
            ->with('ok', '¡Tu búsqueda fue publicada! Pronto recibirás ofertas.');
    }
}
