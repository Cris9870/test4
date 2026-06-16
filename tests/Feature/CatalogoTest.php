<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // ProductoSeeder => 31 productos
    }

    public function test_home_carga_y_muestra_catalogo_y_panel_estado(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('iPhone')        // hay productos en la grilla
            ->assertSee('PostgreSQL');   // panel de estado
    }

    public function test_buscar_devuelve_solo_el_parcial_con_coincidencias(): void
    {
        $res = $this->get('/buscar?q=iphone')->assertOk();
        $res->assertSee('iPhone');
        $res->assertDontSee('<!DOCTYPE html>', false); // es un parcial, no la página completa
    }

    public function test_buscar_filtra_por_categoria(): void
    {
        $this->get('/buscar?categoria=Hogar')
            ->assertOk()
            ->assertSee('Cafetera')        // producto de Hogar
            ->assertDontSee('Samsung Galaxy'); // producto de Electronica, no debe aparecer
    }

    public function test_buscar_sin_resultados(): void
    {
        $this->get('/buscar?q=zzznoexistezzz')
            ->assertOk()
            ->assertSee('No se encontraron');
    }

    public function test_detalle_de_producto(): void
    {
        $id = Producto::query()->value('id');

        $this->get("/producto/{$id}")
            ->assertOk()
            ->assertSee('Descripcion');
    }

    public function test_detalle_inexistente_da_404(): void
    {
        $this->get('/producto/999999')->assertNotFound();
    }
}
