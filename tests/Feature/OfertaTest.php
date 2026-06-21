<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\Oferta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfertaTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_oferta_en_anuncio_ajeno(): void
    {
        $comprador = User::factory()->create();
        $vendedor = User::factory()->create();
        $anuncio = Anuncio::factory()->create(['user_id' => $comprador->id, 'estado' => 'abierto']);

        $this->actingAs($vendedor)->post("/anuncio/{$anuncio->id}/oferta", [
            'precio' => 250,
            'mensaje' => 'Yo tengo uno casi nuevo',
        ])->assertRedirect();

        $this->assertDatabaseHas('ofertas', [
            'anuncio_id' => $anuncio->id,
            'user_id' => $vendedor->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_no_puede_ofertar_en_su_propio_anuncio(): void
    {
        $comprador = User::factory()->create();
        $anuncio = Anuncio::factory()->create(['user_id' => $comprador->id, 'estado' => 'abierto']);

        $this->actingAs($comprador)
            ->post("/anuncio/{$anuncio->id}/oferta", ['precio' => 100])
            ->assertForbidden();
    }

    public function test_no_puede_ofertar_en_anuncio_cerrado(): void
    {
        $comprador = User::factory()->create();
        $vendedor = User::factory()->create();
        $anuncio = Anuncio::factory()->create(['user_id' => $comprador->id, 'estado' => 'cerrado']);

        $this->actingAs($vendedor)
            ->post("/anuncio/{$anuncio->id}/oferta", ['precio' => 100])
            ->assertForbidden();
    }

    public function test_comprador_acepta_oferta_y_cierra_anuncio(): void
    {
        $comprador = User::factory()->create();
        $v1 = User::factory()->create();
        $v2 = User::factory()->create();
        $anuncio = Anuncio::factory()->create(['user_id' => $comprador->id, 'estado' => 'abierto']);
        $oferta1 = Oferta::factory()->create(['anuncio_id' => $anuncio->id, 'user_id' => $v1->id, 'estado' => 'pendiente']);
        $oferta2 = Oferta::factory()->create(['anuncio_id' => $anuncio->id, 'user_id' => $v2->id, 'estado' => 'pendiente']);

        $this->actingAs($comprador)->post("/oferta/{$oferta1->id}/aceptar")->assertRedirect();

        $this->assertDatabaseHas('ofertas', ['id' => $oferta1->id, 'estado' => 'aceptada']);
        $this->assertDatabaseHas('ofertas', ['id' => $oferta2->id, 'estado' => 'rechazada']);
        $this->assertDatabaseHas('anuncios', ['id' => $anuncio->id, 'estado' => 'cerrado']);
    }

    public function test_no_dueno_no_puede_aceptar(): void
    {
        $comprador = User::factory()->create();
        $otro = User::factory()->create();
        $anuncio = Anuncio::factory()->create(['user_id' => $comprador->id, 'estado' => 'abierto']);
        $oferta = Oferta::factory()->create(['anuncio_id' => $anuncio->id, 'user_id' => $otro->id, 'estado' => 'pendiente']);

        $this->actingAs($otro)->post("/oferta/{$oferta->id}/aceptar")->assertForbidden();
    }
}
