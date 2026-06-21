<?php

namespace Tests;

use App\Models\Anuncio;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // En tests no hay Meilisearch: evitamos que crear/sembrar Anuncios intente indexar.
        // La búsqueda (lectura) sí pasa por Scout → Meili "caído" → fallback a PostgreSQL.
        Anuncio::disableSearchSyncing();
    }
}
