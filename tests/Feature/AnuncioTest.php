<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnuncioTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_carga_y_muestra_anuncios_y_panel_estado(): void
    {
        $cat = Categoria::factory()->create(['nombre' => 'Hogar y muebles']);
        Anuncio::factory()->create([
            'titulo' => 'SofaDePruebaUnico',
            'categoria' => 'Hogar y muebles',
            'categoria_id' => $cat->id,
            'estado' => 'abierto',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('SofaDePruebaUnico') // hay anuncios en el muro
            ->assertSee('PostgreSQL');       // panel de estado
    }

    public function test_buscar_devuelve_solo_el_parcial_con_coincidencias(): void
    {
        $cat = Categoria::factory()->create(['nombre' => 'Deportes']);
        Anuncio::factory()->create([
            'titulo' => 'BicicletaUnicaDePrueba',
            'categoria' => 'Deportes',
            'categoria_id' => $cat->id,
            'estado' => 'abierto',
        ]);

        $res = $this->get('/buscar?q=BicicletaUnica')->assertOk();
        $res->assertSee('BicicletaUnicaDePrueba');
        $res->assertDontSee('<!DOCTYPE html>', false); // es un parcial, no la pagina completa
    }

    public function test_buscar_filtra_por_categoria(): void
    {
        $hogar = Categoria::factory()->create(['nombre' => 'Hogar y muebles']);
        $tec = Categoria::factory()->create(['nombre' => 'Tecnología']);
        Anuncio::factory()->create(['titulo' => 'MesaHogarUnica', 'categoria' => 'Hogar y muebles', 'categoria_id' => $hogar->id, 'estado' => 'abierto']);
        Anuncio::factory()->create(['titulo' => 'LaptopTecUnica', 'categoria' => 'Tecnología', 'categoria_id' => $tec->id, 'estado' => 'abierto']);

        $this->get('/buscar?categoria=' . urlencode('Hogar y muebles'))
            ->assertOk()
            ->assertSee('MesaHogarUnica')
            ->assertDontSee('LaptopTecUnica'); // de otra categoria, no debe aparecer
    }

    public function test_buscar_sin_resultados_no_muestra_anuncios(): void
    {
        Anuncio::factory()->create(['titulo' => 'AlgoQueSiExiste', 'estado' => 'abierto']);

        $this->get('/buscar?q=zzznoexistezzz')
            ->assertOk()
            ->assertDontSee('AlgoQueSiExiste');
    }

    public function test_detalle_de_anuncio(): void
    {
        $anuncio = Anuncio::factory()->create(['titulo' => 'TaladroDetalleUnico', 'estado' => 'abierto']);

        $this->get("/anuncio/{$anuncio->id}")
            ->assertOk()
            ->assertSee('TaladroDetalleUnico');
    }

    public function test_detalle_inexistente_da_404(): void
    {
        $this->get('/anuncio/999999')->assertNotFound();
    }
}
