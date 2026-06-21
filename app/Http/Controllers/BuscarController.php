<?php

namespace App\Http\Controllers;

use App\Services\BuscadorAnuncios;
use Illuminate\Http\Request;

/**
 * Endpoint htmx: devuelve SOLO el parcial de resultados (grilla + facetas),
 * que reemplaza el contenedor #resultados sin recargar la pagina.
 */
class BuscarController extends Controller
{
    public function __invoke(Request $request, BuscadorAnuncios $buscador)
    {
        $vm = $buscador->buscar(
            (string) $request->query('q', ''),
            (string) $request->query('categoria', '')
        );

        return view('partials.resultados', $vm);
    }
}
