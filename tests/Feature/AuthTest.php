<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_crea_usuario_y_autentica(): void
    {
        $this->post('/registro', [
            'name' => 'Juan Test',
            'email' => 'juan@test.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect('/cuenta');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'juan@test.com']);
    }

    public function test_cuenta_esta_protegida(): void
    {
        $this->get('/cuenta')->assertRedirect('/login');
    }

    public function test_login_correcto_y_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret1234']); // el cast 'hashed' la hashea

        $this->post('/login', ['email' => $user->email, 'password' => 'secret1234'])
            ->assertRedirect('/cuenta');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_con_credenciales_invalidas(): void
    {
        User::factory()->create(['email' => 'a@test.com', 'password' => 'secret1234']);

        $this->from('/login')
            ->post('/login', ['email' => 'a@test.com', 'password' => 'incorrecta'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_logueado_ve_su_cuenta(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/cuenta')
            ->assertOk()
            ->assertSee($user->email);
    }
}
