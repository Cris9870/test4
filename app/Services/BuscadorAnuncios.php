<?php

namespace App\Services;

use App\Models\Anuncio;

/**
 * Encapsula la búsqueda de Anuncios (solicitudes de compra) contra Meilisearch
 * (vía Scout) y devuelve un "view-model" uniforme que consumen tanto la home
 * como el endpoint htmx.
 *
 * Si Meilisearch falla, degrada a una búsqueda en PostgreSQL (Eloquent) para
 * que la página no se caiga (tolerancia a fallos por diseño).
 */
class BuscadorAnuncios
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
        // 1) Hits filtrados por texto + categoria (lo que se muestra en el muro).
        //    Solo anuncios abiertos viven en el índice (shouldBeSearchable).
        $hits = Anuncio::search($q, function ($index, string $query, array $options) use ($categoria, $limit) {
            $options['limit'] = $limit;
            if ($categoria !== '') {
                $options['filter'] = 'categoria = "' . str_replace('"', '\"', $categoria) . '"';
            }

            return $index->search($query, $options);
        })->raw();

        // 2) Distribución de facetas SIN el filtro de categoria (facetas disjuntas).
        $facetas = Anuncio::search($q, function ($index, string $query, array $options) {
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
                    $w->where('titulo', 'ilike', "%{$q}%")
                        ->orWhere('descripcion', 'ilike', "%{$q}%")
                        ->orWhere('categoria', 'ilike', "%{$q}%");
                });
            }
        };

        // Solo anuncios abiertos (igual que el índice de Meili).
        $facetsQuery = Anuncio::query()->where('estado', 'abierto');
        $aplicarTexto($facetsQuery);
        $facets = $facetsQuery->selectRaw('categoria, count(*) as c')
            ->groupBy('categoria')
            ->pluck('c', 'categoria')
            ->toArray();

        $itemsQuery = Anuncio::query()->where('estado', 'abierto')->withCount('ofertas');
        $aplicarTexto($itemsQuery);
        if ($categoria !== '') {
            $itemsQuery->where('categoria', $categoria);
        }
        $items = $itemsQuery->orderByDesc('created_at')->get();

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
