<?php

namespace Database\Seeders;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnuncioSeeder extends Seeder
{
    public function run(): void
    {
        $cats = Categoria::pluck('id', 'nombre');          // nombre => id
        $users = User::where('es_admin', false)->pluck('id', 'email'); // email => id

        // [titulo, categoria, presupuesto S/, ciudad, estado, comprador, keyword-imagen]
        // La imagen se trae temática por palabra clave (LoremFlickr) para que coincida
        // con el artículo de la publicación (no paisajes aleatorios).
        $items = [
            ['un sofá de 2 cuerpos en buen estado', 'Hogar y muebles', 300, 'Lima', 'abierto', 'laura@reversa.pe', 'sofa'],
            ['una bicicleta para ir al trabajo', 'Deportes', 250, 'Arequipa', 'abierto', 'andres@reversa.pe', 'bicycle'],
            ['una licuadora que funcione bien', 'Electrodomésticos', 80, 'Lima', 'abierto', 'mariana@reversa.pe', 'blender'],
            ['un coche/silla para bebé', 'Bebés', 180, 'Trujillo', 'abierto', 'valentina@reversa.pe', 'stroller'],
            ['un escritorio para estudiar/trabajar', 'Oficina', 150, 'Cusco', 'abierto', 'sebastian@reversa.pe', 'desk'],
            ['una consola para los niños', 'Tecnología', 400, 'Lima', 'abierto', 'laura@reversa.pe', 'videogame-console'],
            ['un microondas en buen estado', 'Electrodomésticos', 120, 'Piura', 'abierto', 'andres@reversa.pe', 'microwave'],
            ['una mesa de comedor para 4', 'Hogar y muebles', 280, 'Lima', 'abierto', 'mariana@reversa.pe', 'dining-table'],
            ['una laptop para la universidad', 'Tecnología', 900, 'Arequipa', 'abierto', 'sebastian@reversa.pe', 'laptop'],
            ['una silla de oficina ergonómica', 'Oficina', 160, 'Lima', 'abierto', 'valentina@reversa.pe', 'office-chair'],
            ['un televisor de 32 pulgadas', 'Tecnología', 320, 'Trujillo', 'abierto', 'laura@reversa.pe', 'television'],
            ['un taladro para arreglos en casa', 'Herramientas', 120, 'Lima', 'abierto', 'andres@reversa.pe', 'drill'],
            ['una cafetera en buen estado', 'Cocina', 90, 'Cusco', 'abierto', 'mariana@reversa.pe', 'coffee-maker'],
            ['una cuna para bebé', 'Bebés', 200, 'Lima', 'abierto', 'sebastian@reversa.pe', 'baby-crib'],
            ['una refrigeradora pequeña', 'Electrodomésticos', 600, 'Lima', 'cerrado', 'valentina@reversa.pe', 'refrigerator'],
            ['una bicicleta de montaña', 'Deportes', 500, 'Arequipa', 'cerrado', 'laura@reversa.pe', 'mountain-bike'],
        ];

        foreach ($items as $i => [$titulo, $cat, $presupuesto, $ciudad, $estado, $email, $kw]) {
            Anuncio::updateOrCreate(
                ['titulo' => $titulo],
                [
                    'user_id' => $users[$email],
                    'categoria_id' => $cats[$cat] ?? null,
                    'categoria' => $cat,
                    'descripcion' => "Busco {$titulo}. Pago en soles al contado; coordino recojo o envío dentro de la ciudad. Que esté en buen estado, ¡gracias!",
                    'presupuesto' => $presupuesto,
                    'ciudad' => $ciudad,
                    // Imagen temática y estable (lock por índice) acorde al artículo.
                    'imagen_url' => "https://loremflickr.com/600/450/{$kw}?lock=".($i + 1),
                    'estado' => $estado,
                ],
            );
        }
    }
}
