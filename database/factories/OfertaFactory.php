<?php

namespace Database\Factories;

use App\Models\Anuncio;
use App\Models\Oferta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Oferta>
 */
class OfertaFactory extends Factory
{
    protected $model = Oferta::class;

    public function definition(): array
    {
        return [
            'anuncio_id' => Anuncio::factory(),
            'user_id' => User::factory(),
            'precio' => fake()->numberBetween(100, 1000),
            'mensaje' => fake()->sentence(8),
            'imagen_url' => null,
            'estado' => 'pendiente',
        ];
    }

    public function aceptada(): static
    {
        return $this->state(fn () => ['estado' => 'aceptada']);
    }

    public function rechazada(): static
    {
        return $this->state(fn () => ['estado' => 'rechazada']);
    }
}
