<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Oferta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OfertaController extends Controller
{
    public function store(int $anuncioId, Request $request)
    {
        $anuncio = Anuncio::findOrFail($anuncioId);

        abort_if($anuncio->user_id === $request->user()->id, 403, 'No puedes ofertar en tu propia búsqueda.');
        abort_if($anuncio->estado !== 'abierto', 403, 'Esta búsqueda ya está cerrada.');

        $data = $request->validate([
            'precio' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'mensaje' => ['nullable', 'string', 'max:1000'],
            'imagen' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('ofertas', 'public');
            $imagenUrl = Storage::disk('public')->url($path);
        }

        Oferta::create([
            'anuncio_id' => $anuncio->id,
            'user_id' => $request->user()->id,
            'precio' => $data['precio'],
            'mensaje' => $data['mensaje'] ?? null,
            'imagen_url' => $imagenUrl,
            'estado' => 'pendiente',
        ]);

        return redirect()->route('anuncio.show', $anuncio->id)
            ->with('ok', 'Tu oferta fue enviada. El comprador la verá y podrá aceptarla.');
    }

    /**
     * El comprador (dueño del anuncio) acepta una oferta: la marca aceptada,
     * rechaza las demás y cierra el anuncio (sale del muro de búsqueda).
     */
    public function aceptar(int $ofertaId, Request $request)
    {
        $oferta = Oferta::with('anuncio')->findOrFail($ofertaId);
        $anuncio = $oferta->anuncio;

        abort_unless($anuncio->user_id === $request->user()->id, 403);
        abort_if($anuncio->estado !== 'abierto', 403, 'Esta búsqueda ya está cerrada.');

        DB::transaction(function () use ($oferta, $anuncio) {
            $anuncio->ofertas()->where('id', '!=', $oferta->id)->update(['estado' => 'rechazada']);
            $oferta->update(['estado' => 'aceptada']);
            $anuncio->update(['estado' => 'cerrado']); // Scout des-indexa (shouldBeSearchable)
        });

        return redirect()->route('anuncio.show', $anuncio->id)
            ->with('ok', 'Aceptaste la oferta de ' . $oferta->vendedor->name . '. La búsqueda quedó cerrada.');
    }
}
