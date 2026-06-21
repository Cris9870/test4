<?php

namespace Database\Factories;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Anuncio>
 */
class AnuncioFactory extends Factory
{
    protected $model = Anuncio::class;

    public function definition(): array
    {
        $titulo = fake()->randomElement([
            'un sofá de 2 cuerpos',
            'una bicicleta urbana',
            'una licuadora que funcione',
            'un televisor de 32 pulgadas',
            'una laptop para estudiar',
            'una cafetera en buen estado',
            'un escritorio de madera',
        ]);
        $categoria = fake()->randomElement([
            'Hogar y muebles', 'Tecnología', 'Deportes', 'Cocina', 'Electrodomésticos', 'Oficina',
        ]);

        return [
            'user_id' => User::factory(),
            'categoria_id' => Categoria::factory(),
            'titulo' => $titulo,
            'descripcion' => fake()->sentence(12),
            'categoria' => $categoria,
            'presupuesto' => fake()->numberBetween(100, 1000),
            'imagen_url' => 'https://picsum.photos/seed/'.Str::slug($titulo).fake()->numberBetween(1, 9999).'/600/450',
            'estado' => 'abierto',
            'ciudad' => 'Lima',
        ];
    }

    public function cerrado(): static
    {
        return $this->state(fn () => ['estado' => 'cerrado']);
    }
}
