<?php

namespace App\Services;

use App\Models\Producto;

/**
 * Encapsula la busqueda contra Meilisearch (via Scout) y devuelve un
 * "view-model" uniforme que consumen tanto la home como el endpoint htmx.
 *
 * Si Meilisearch falla, degrada a una busqueda en PostgreSQL (Eloquent)
 * para que la pagina no se caiga (tolerancia pedida en el enunciado).
 */
class BuscadorProductos
{
    public function buscar(string $q, string $categoria = '', int $limit = 100): array
    {
        $q = trim($q);
        $categoria = trim($categoria);

        try {
            return $this->viaMeilisearch($q, $categoria, $limit);
        } catch (\Throwable $e) {
            return $this->viaPostgres($q, $categoria, $e->getMessage());
        }
    }

    private function viaMeilisearch(string $q, string $categoria, int $limit): array
    {
        // 1) Hits filtrados por texto + categoria (lo que se muestra en la grilla).
        $hits = Producto::search($q, function ($index, string $query, array $options) use ($categoria, $limit) {
            $options['limit'] = $limit;
            if ($categoria !== '') {
                // Escapamos comillas para no romper la expresion de filtro de Meili
                $options['filter'] = 'categoria = "' . str_replace('"', '\"', $categoria) . '"';
            }

            return $index->search($query, $options);
        })->raw();

        // 2) Distribucion de facetas SIN el filtro de categoria, para que el usuario
        //    pueda cambiar de faceta (facetas disjuntas). Solo cuenta, sin traer hits.
        $facetas = Producto::search($q, function ($index, string $query, array $options) {
            $options['limit'] = 0;
            $options['facets'] = ['categoria'];

            return $index->search($query, $options);
        })->raw();

        return [
            'items' => $hits['hits'] ?? [],
            'total' => $hits['estimatedTotalHits'] ?? count($hits['hits'] ?? []),
            'processingTimeMs' => $hits['processingTimeMs'] ?? null,
            'facets' => $facetas['facetDistribution']['categoria'] ?? [],
            'q' => $q,
            'categoria' => $categoria,
            'fuente' => 'meilisearch',
            'error' => null,
        ];
    }

    private function viaPostgres(string $q, string $categoria, string $error): array
    {
        $aplicarTexto = function ($query) use ($q) {
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('descripcion', 'ilike', "%{$q}%")
                        ->orWhere('categoria', 'ilike', "%{$q}%");
                });
            }
        };

        // Conteo por categoria (ignorando el filtro de categoria, para poder cambiar de faceta)
        $facetsQuery = Producto::query();
        $aplicarTexto($facetsQuery);
        $facets = $facetsQuery->selectRaw('categoria, count(*) as c')
            ->groupBy('categoria')
            ->pluck('c', 'categoria')
            ->toArray();

        $itemsQuery = Producto::query();
        $aplicarTexto($itemsQuery);
        if ($categoria !== '') {
            $itemsQuery->where('categoria', $categoria);
        }
        $items = $itemsQuery->orderBy('nombre')->get();

        return [
            'items' => $items,
            'total' => $items->count(),
            'processingTimeMs' => null,
            'facets' => $facets,
            'q' => $q,
            'categoria' => $categoria,
            'fuente' => 'postgresql',
            'error' => $error,
        ];
    }
}
