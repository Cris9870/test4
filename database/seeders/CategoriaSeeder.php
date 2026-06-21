<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['Hogar y muebles', '🛋️'],
            ['Electrodomésticos', '🔌'],
            ['Tecnología', '💻'],
            ['Bebés', '🍼'],
            ['Deportes', '⚽'],
            ['Herramientas', '🔧'],
            ['Oficina', '🗂️'],
            ['Cocina', '🍳'],
        ];

        foreach ($categorias as [$nombre, $icono]) {
            Categoria::updateOrCreate(
                ['slug' => Str::slug($nombre)],
                ['nombre' => $nombre, 'icono' => $icono],
            );
        }
    }
}
