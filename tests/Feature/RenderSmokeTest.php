<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Oferta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test de render: confirma que cada vista compila y se renderiza sin
 * errores de runtime (variables faltantes, rutas inexistentes, etc.).
 */
class RenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginas_publicas_renderizan(): void
    {
        $cat = Categoria::factory()->create();
        $anuncio = Anuncio::factory()->create(['categoria_id' => $cat->id, 'estado' => 'abierto']);

        $this->get('/')->assertOk();
        $this->get('/buscar')->assertOk();
        $this->get('/categoria/' . $cat->slug)->assertOk();
        $this->get('/anuncio/' . $anuncio->id)->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/registro')->assertOk();
        $this->get('/subir')->assertOk();
    }

    public function test_paginas_autenticadas_renderizan(): void
    {
        $user = User::factory()->create();
        Categoria::factory()->count(2)->create();
        $anuncio = Anuncio::factory()->create(['estado' => 'abierto']); // de otro comprador
        Oferta::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $this->get('/cuenta')->assertOk();
        $this->get('/publicar')->assertOk();
        $this->get('/anuncio/' . $anuncio->id)->assertOk(); // no-dueño ve el form de oferta
    }

    public function test_paginas_admin_renderizan(): void
    {
        $admin = User::factory()->admin()->create();
        $cat = Categoria::factory()->create();
        $anuncio = Anuncio::factory()->create(['categoria_id' => $cat->id]);
        Oferta::factory()->create(['anuncio_id' => $anuncio->id]);

        $this->actingAs($admin);
        $this->get('/admin')->assertOk();
        $this->get('/admin/anuncios')->assertOk();
        $this->get('/admin/ofertas')->assertOk();
        $this->get('/admin/usuarios')->assertOk();
        $this->get('/admin/categorias')->assertOk();
    }
}
