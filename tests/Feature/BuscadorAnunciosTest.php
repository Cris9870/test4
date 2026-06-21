<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Services\BuscadorAnuncios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscadorAnunciosTest extends TestCase
{
    use RefreshDatabase;

    public function test_degrada_a_postgresql_cuando_meili_no_responde(): void
    {
        // En tests MEILISEARCH_HOST es invalido => el servicio debe caer al fallback PG.
        $cat = Categoria::factory()->create(['nombre' => 'Tecnología']);
        Anuncio::factory()->create(['titulo' => 'laptop gamer barata', 'categoria' => 'Tecnología', 'categoria_id' => $cat->id, 'estado' => 'abierto']);

        $vm = app(BuscadorAnuncios::class)->buscar('laptop');

        $this->assertSame('postgresql', $vm['fuente']);
        $this->assertGreaterThan(0, $vm['total']);

        $titulos = collect($vm['items'])->pluck('titulo')->implode(' | ');
        $this->assertStringContainsStringIgnoringCase('laptop', $titulos);
    }

    public function test_calcula_facetas_por_categoria(): void
    {
        $cat = Categoria::factory()->create(['nombre' => 'Deportes']);
        Anuncio::factory()->count(2)->create(['categoria' => 'Deportes', 'categoria_id' => $cat->id, 'estado' => 'abierto']);

        $vm = app(BuscadorAnuncios::class)->buscar('');

        $this->assertArrayHasKey('Deportes', $vm['facets']);
        $this->assertGreaterThan(0, $vm['facets']['Deportes']);
    }

    public function test_filtro_combinado_sin_coincidencias(): void
    {
        $tec = Categoria::factory()->create(['nombre' => 'Tecnología']);
        Anuncio::factory()->create(['titulo' => 'laptop', 'categoria' => 'Tecnología', 'categoria_id' => $tec->id, 'estado' => 'abierto']);

        // 'laptop' es Tecnología; filtrar por Deportes no debe devolver nada.
        $vm = app(BuscadorAnuncios::class)->buscar('laptop', 'Deportes');

        $this->assertSame(0, $vm['total']);
    }

    public function test_anuncios_cerrados_no_aparecen_en_el_muro(): void
    {
        $cat = Categoria::factory()->create(['nombre' => 'Hogar y muebles']);
        Anuncio::factory()->create(['titulo' => 'AbiertoVisible', 'categoria' => 'Hogar y muebles', 'categoria_id' => $cat->id, 'estado' => 'abierto']);
        Anuncio::factory()->create(['titulo' => 'CerradoOculto', 'categoria' => 'Hogar y muebles', 'categoria_id' => $cat->id, 'estado' => 'cerrado']);

        $vm = app(BuscadorAnuncios::class)->buscar('');
        $titulos = collect($vm['items'])->pluck('titulo');

        $this->assertTrue($titulos->contains('AbiertoVisible'));
        $this->assertFalse($titulos->contains('CerradoOculto'));
    }
}
