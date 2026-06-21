<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicarTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_publicar(): void
    {
        $this->get('/publicar')->assertRedirect('/login');
    }

    public function test_usuario_publica_un_anuncio_con_imagen(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $cat = Categoria::factory()->create(['nombre' => 'Tecnología']);

        $this->actingAs($user)->post('/publicar', [
            'titulo' => 'una laptop para la universidad',
            'descripcion' => 'Que tenga buena bateria',
            'categoria_id' => $cat->id,
            'presupuesto' => 1200,
            'ciudad' => 'Lima',
            'imagen' => UploadedFile::fake()->image('foto.jpg', 300, 300),
        ])->assertRedirect();

        $this->assertDatabaseHas('anuncios', [
            'titulo' => 'una laptop para la universidad',
            'user_id' => $user->id,
            'categoria' => 'Tecnología',
            'categoria_id' => $cat->id,
            'estado' => 'abierto',
        ]);
        $this->assertNotEmpty(Storage::disk('public')->files('anuncios'));
    }

    public function test_publicar_valida_campos_requeridos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/publicar')
            ->post('/publicar', ['titulo' => ''])
            ->assertSessionHasErrors(['titulo', 'descripcion', 'categoria_id', 'presupuesto']);
    }
}
