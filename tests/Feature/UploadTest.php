<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_sube_una_imagen_al_disco_public(): void
    {
        Storage::fake('public');

        $this->post('/subir', ['imagen' => UploadedFile::fake()->image('foto.jpg', 200, 200)])
            ->assertRedirect('/subir')
            ->assertSessionHas('ok');

        $this->assertNotEmpty(Storage::disk('public')->files('uploads'));
    }

    public function test_rechaza_archivos_que_no_son_imagen(): void
    {
        Storage::fake('public');

        $this->from('/subir')
            ->post('/subir', ['imagen' => UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf')])
            ->assertSessionHasErrors('imagen');

        $this->assertEmpty(Storage::disk('public')->files('uploads'));
    }

    public function test_diagnostico_de_upload_responde_json(): void
    {
        Storage::fake('public');

        $this->get('/infra/upload-test')
            ->assertOk()
            ->assertJsonStructure(['written', 'url', 'public_storage_symlink']);
    }
}
