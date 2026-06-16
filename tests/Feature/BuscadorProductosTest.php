<?php

namespace Tests\Feature;

use App\Services\BuscadorProductos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscadorProductosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_degrada_a_postgresql_cuando_meili_no_responde(): void
    {
        // En tests MEILISEARCH_HOST es invalido => el servicio debe caer al fallback PG.
        $vm = app(BuscadorProductos::class)->buscar('iphone');

        $this->assertSame('postgresql', $vm['fuente']);
        $this->assertGreaterThan(0, $vm['total']);

        $nombres = collect($vm['items'])->pluck('nombre')->implode(' | ');
        $this->assertStringContainsStringIgnoringCase('iphone', $nombres);
    }

    public function test_calcula_facetas_por_categoria(): void
    {
        $vm = app(BuscadorProductos::class)->buscar('');

        $this->assertArrayHasKey('Electronica', $vm['facets']);
        $this->assertGreaterThan(0, $vm['facets']['Electronica']);
    }

    public function test_filtro_combinado_sin_coincidencias(): void
    {
        // iPhone es Electronica; filtrar por Hogar no debe devolver nada.
        $vm = app(BuscadorProductos::class)->buscar('iphone', 'Hogar');

        $this->assertSame(0, $vm['total']);
    }
}
