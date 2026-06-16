<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    public function show(int $id)
    {
        // URL indexable, SSR. 404 si no existe.
        $producto = Producto::findOrFail($id);

        // Galeria sintetica (varias "vistas") para la microinteraccion Alpine.
        // El producto tiene una sola imagen real; derivamos miniaturas estables.
        $slug = Str::slug($producto->nombre);
        $galeria = [
            $producto->imagen_url,
            "https://picsum.photos/seed/{$slug}-b/800/600",
            "https://picsum.photos/seed/{$slug}-c/800/600",
            "https://picsum.photos/seed/{$slug}-d/800/600",
        ];

        return view('producto', compact('producto', 'galeria'));
    }
}
