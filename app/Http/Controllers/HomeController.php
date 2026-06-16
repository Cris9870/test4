<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\BuscadorProductos;
use Illuminate\Http\Request;
use Meilisearch\Client;

class HomeController extends Controller
{
    public function index(Request $request, BuscadorProductos $buscador)
    {
        // Render inicial (SSR) del catalogo completo via el mismo camino de busqueda.
        $vm = $buscador->buscar('', '');
        $vm['estado'] = $this->estadoServicios();

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
            $n = Producto::count();
            $pg = ['ok' => true, 'detalle' => "{$n} productos en catalogo"];
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
