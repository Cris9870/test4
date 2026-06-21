<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Services\BuscadorAnuncios;
use Illuminate\Http\Request;
use Meilisearch\Client;

class HomeController extends Controller
{
    public function index(Request $request, BuscadorAnuncios $buscador)
    {
        // Render inicial (SSR). El buscador del header envía ?q por GET (funciona en
        // cualquier página); la home además trae el buscador htmx en vivo.
        $vm = $buscador->buscar((string) $request->query('q', ''), '');
        $vm['estado'] = $this->estadoServicios();

        return view('home', $vm);
    }

    /**
     * Listado pre-filtrado por categoría (reusa el muro de la home).
     */
    public function categoria(string $slug, BuscadorAnuncios $buscador)
    {
        $categoria = Categoria::where('slug', $slug)->firstOrFail();

        $vm = $buscador->buscar('', $categoria->nombre);
        $vm['estado'] = $this->estadoServicios();
        $vm['categoriaActiva'] = $categoria;

        return view('home', $vm);
    }

    /**
     * Panel de salud: cada chequeo en su propio try/catch para que la caida
     * de un servicio NO tumbe la pagina. Incluye timestamp del servidor.
     */
    private function estadoServicios(): array
    {
        // --- PostgreSQL: query REAL via Eloquent ---
        try {
            $n = Anuncio::count();
            $pg = ['ok' => true, 'detalle' => "{$n} anuncios publicados"];
        } catch (\Throwable $e) {
            $pg = ['ok' => false, 'detalle' => $this->corto($e->getMessage())];
        }

        // --- Meilisearch: chequeo de salud ---
        try {
            $client = new Client(
                config('scout.meilisearch.host'),
                config('scout.meilisearch.key')
            );
            $salud = $client->health();
            $ok = ($salud['status'] ?? null) === 'available';
            $meili = ['ok' => $ok, 'detalle' => $ok ? 'available' : json_encode($salud)];
        } catch (\Throwable $e) {
            $meili = ['ok' => false, 'detalle' => $this->corto($e->getMessage())];
        }

        return [
            'pg' => $pg,
            'meili' => $meili,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'tz' => config('app.timezone', 'UTC'),
        ];
    }

    private function corto(string $s): string
    {
        return mb_strlen($s) > 130 ? mb_substr($s, 0, 130) . '…' : $s;
    }
}
