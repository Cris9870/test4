<?php

namespace Tests;

use App\Models\Producto;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // En tests no hay Meilisearch: evitamos que crear/sembrar Productos intente indexar.
        // La búsqueda (lectura) sí pasa por Scout → Meili "caído" → fallback a PostgreSQL.
        Producto::disableSearchSyncing();
    }
}
