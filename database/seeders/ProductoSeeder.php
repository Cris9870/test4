<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            // --- Electronica ---
            ['Apple iPhone 15 128GB', 'Smartphone con chip A16 Bionic, pantalla Super Retina XDR de 6.1" y camara dual de 48 MP.', 'Electronica', 999.00, 25],
            ['Samsung Galaxy S24', 'Telefono Android con pantalla Dynamic AMOLED 2X, 256GB y camara de 50 MP con IA.', 'Electronica', 849.99, 18],
            ['Laptop HP Pavilion 15', 'Portatil con procesador Intel Core i7, 16GB RAM y SSD de 512GB. Ideal para trabajo y estudio.', 'Electronica', 749.00, 12],
            ['Laptop Dell XPS 13', 'Ultrabook ligero con pantalla InfinityEdge, Core i5 de 13a gen y 8GB de RAM.', 'Electronica', 1199.00, 7],
            ['Auriculares Sony WH-1000XM5', 'Audifonos inalambricos con cancelacion de ruido lider en su clase y 30h de bateria.', 'Electronica', 379.00, 40],
            ['Smart TV LG OLED 55"', 'Televisor 4K UHD con panel OLED, HDR10 y webOS para streaming.', 'Electronica', 1099.00, 9],
            ['Tablet iPad Air', 'Tablet con chip M1, pantalla Liquid Retina de 10.9" y compatibilidad con Apple Pencil.', 'Electronica', 599.00, 22],
            ['Monitor Gamer 27" 165Hz', 'Monitor QHD con panel IPS, 1ms de respuesta y FreeSync para gaming fluido.', 'Electronica', 289.99, 30],

            // --- Hogar ---
            ['Cafetera Espresso Delonghi', 'Cafetera automatica con molinillo integrado y espumador de leche para capuchinos.', 'Hogar', 449.00, 15],
            ['Aspiradora Robot Roomba', 'Robot aspirador con mapeo inteligente, control por app y vaciado automatico.', 'Hogar', 599.00, 11],
            ['Juego de Sartenes Antiadherentes', 'Set de 3 sartenes con recubrimiento ceramico libre de PFOA, aptas para induccion.', 'Hogar', 79.90, 50],
            ['Licuadora de Alta Potencia', 'Licuadora de 1200W con vaso de vidrio de 1.5L y 6 velocidades.', 'Hogar', 89.99, 35],
            ['Set de Sabanas de Algodon', 'Juego de sabanas 100% algodon egipcio de 400 hilos, tamano queen.', 'Hogar', 59.99, 60],
            ['Lampara de Pie LED Regulable', 'Lampara moderna con brazo ajustable, temperatura de color regulable y control tactil.', 'Hogar', 64.50, 28],

            // --- Deportes ---
            ['Bicicleta de Montana 29"', 'Bicicleta MTB con cuadro de aluminio, 21 velocidades y frenos de disco.', 'Deportes', 539.00, 8],
            ['Zapatillas Running Nike', 'Zapatillas para correr con amortiguacion React y malla transpirable.', 'Deportes', 129.99, 45],
            ['Mancuernas Ajustables 24kg', 'Par de mancuernas regulables de 2.5 a 24 kg, ideales para entrenamiento en casa.', 'Deportes', 199.00, 14],
            ['Esterilla de Yoga Premium', 'Mat antideslizante de 6mm con correa de transporte, libre de toxicos.', 'Deportes', 34.90, 70],
            ['Balon de Futbol Profesional', 'Balon talla 5 cosido a maquina, apto para cesped natural y artificial.', 'Deportes', 39.99, 55],

            // --- Moda ---
            ['Chaqueta Impermeable Outdoor', 'Chaqueta cortavientos con membrana impermeable y capucha ajustable.', 'Moda', 119.00, 26],
            ['Reloj Inteligente Garmin', 'Smartwatch con GPS, monitor de frecuencia cardiaca y hasta 7 dias de bateria.', 'Moda', 249.99, 19],
            ['Mochila Antirrobo USB', 'Mochila urbana resistente al agua con puerto USB y compartimento para laptop.', 'Moda', 49.90, 48],
            ['Gafas de Sol Polarizadas', 'Lentes con proteccion UV400 y montura de acetato resistente.', 'Moda', 89.00, 33],

            // --- Libros ---
            ['Libro: El Quijote (Edicion Ilustrada)', 'Edicion de tapa dura ilustrada de la obra maestra de Cervantes.', 'Libros', 29.90, 40],
            ['Libro: Sapiens de Yuval Noah Harari', 'Un recorrido por la historia de la humanidad, best seller internacional.', 'Libros', 22.50, 38],
            ['Libro: Clean Code', 'Guia practica de buenas practicas de programacion por Robert C. Martin.', 'Libros', 44.00, 21],

            // --- Juguetes ---
            ['Set de Construccion LEGO Classic', 'Caja con 500 piezas para construir multiples modelos. Estimula la creatividad.', 'Juguetes', 39.99, 42],
            ['Dron con Camara HD', 'Mini dron con camara 1080p, estabilizacion y 20 minutos de vuelo.', 'Juguetes', 79.00, 17],
            ['Consola Portatil Retro', 'Consola con 400 juegos clasicos preinstalados y pantalla a color de 3".', 'Juguetes', 45.99, 24],

            // --- Belleza ---
            ['Secador de Pelo Ionico', 'Secador profesional de 2200W con tecnologia ionica anti-frizz.', 'Belleza', 69.90, 31],
            ['Set de Brochas de Maquillaje', 'Kit de 12 brochas profesionales con estuche de viaje.', 'Belleza', 27.50, 58],
        ];

        foreach ($productos as [$nombre, $descripcion, $categoria, $precio, $stock]) {
            $slug = Str::slug($nombre);
            Producto::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'categoria' => $categoria,
                'precio' => $precio,
                'stock' => $stock,
                'imagen_url' => "https://picsum.photos/seed/{$slug}/600/450",
            ]);
        }
    }
}
