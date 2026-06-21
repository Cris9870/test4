<?php

namespace Database\Seeders;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnuncioSeeder extends Seeder
{
    public function run(): void
    {
        $cats = Categoria::pluck('id', 'nombre');          // nombre => id
        $users = User::where('es_admin', false)->pluck('id', 'email'); // email => id

        // [titulo, categoria, presupuesto S/, ciudad, estado, comprador]
        $items = [
            ['un sofá de 2 cuerpos en buen estado', 'Hogar y muebles', 300, 'Lima', 'abierto', 'laura@reversa.pe'],
            ['una bicicleta para ir al trabajo', 'Deportes', 250, 'Arequipa', 'abierto', 'andres@reversa.pe'],
            ['una licuadora que funcione bien', 'Electrodomésticos', 80, 'Lima', 'abierto', 'mariana@reversa.pe'],
            ['un coche/silla para bebé', 'Bebés', 180, 'Trujillo', 'abierto', 'valentina@reversa.pe'],
            ['un escritorio para estudiar/trabajar', 'Oficina', 150, 'Cusco', 'abierto', 'sebastian@reversa.pe'],
            ['una consola para los niños', 'Tecnología', 400, 'Lima', 'abierto', 'laura@reversa.pe'],
            ['un microondas en buen estado', 'Electrodomésticos', 120, 'Piura', 'abierto', 'andres@reversa.pe'],
            ['una mesa de comedor para 4', 'Hogar y muebles', 280, 'Lima', 'abierto', 'mariana@reversa.pe'],
            ['una laptop para la universidad', 'Tecnología', 900, 'Arequipa', 'abierto', 'sebastian@reversa.pe'],
            ['una silla de oficina ergonómica', 'Oficina', 160, 'Lima', 'abierto', 'valentina@reversa.pe'],
            ['un televisor de 32 pulgadas', 'Tecnología', 320, 'Trujillo', 'abierto', 'laura@reversa.pe'],
            ['un taladro para arreglos en casa', 'Herramientas', 120, 'Lima', 'abierto', 'andres@reversa.pe'],
            ['una cafetera en buen estado', 'Cocina', 90, 'Cusco', 'abierto', 'mariana@reversa.pe'],
            ['una cuna para bebé', 'Bebés', 200, 'Lima', 'abierto', 'sebastian@reversa.pe'],
            ['una refrigeradora pequeña', 'Electrodomésticos', 600, 'Lima', 'cerrado', 'valentina@reversa.pe'],
            ['una bicicleta de montaña', 'Deportes', 500, 'Arequipa', 'cerrado', 'laura@reversa.pe'],
        ];

        foreach ($items as [$titulo, $cat, $presupuesto, $ciudad, $estado, $email]) {
            Anuncio::updateOrCreate(
                ['titulo' => $titulo],
                [
                    'user_id' => $users[$email],
                    'categoria_id' => $cats[$cat] ?? null,
                    'categoria' => $cat,
                    'descripcion' => "Busco {$titulo}. Pago en soles al contado; coordino recojo o envío dentro de la ciudad. Que esté en buen estado, ¡gracias!",
                    'presupuesto' => $presupuesto,
                    'ciudad' => $ciudad,
                    'imagen_url' => 'https://picsum.photos/seed/'.Str::slug($titulo).'/600/450',
                    'estado' => $estado,
                ],
            );
        }
    }
}
