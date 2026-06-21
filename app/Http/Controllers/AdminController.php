<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Categoria;
use App\Models\Oferta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel de administración hecho a medida (sin paquetes), servido en /admin y
 * protegido por el middleware 'admin' (es_admin = true).
 */
class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'usuarios' => User::count(),
            'anuncios' => Anuncio::count(),
            'anuncios_abiertos' => Anuncio::where('estado', 'abierto')->count(),
            'ofertas' => Oferta::count(),
            'categorias' => Categoria::count(),
        ];
        $ultimos = Anuncio::with('comprador')->withCount('ofertas')->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'ultimos'));
    }

    public function anuncios()
    {
        $anuncios = Anuncio::with('comprador')->withCount('ofertas')->latest()->paginate(20);

        return view('admin.anuncios', compact('anuncios'));
    }

    public function cerrarAnuncio(int $id)
    {
        Anuncio::findOrFail($id)->update(['estado' => 'cerrado']);

        return back()->with('ok', 'Anuncio cerrado.');
    }

    public function eliminarAnuncio(int $id)
    {
        Anuncio::findOrFail($id)->delete();

        return back()->with('ok', 'Anuncio eliminado.');
    }

    public function ofertas()
    {
        $ofertas = Oferta::with(['anuncio', 'vendedor'])->latest()->paginate(20);

        return view('admin.ofertas', compact('ofertas'));
    }

    public function eliminarOferta(int $id)
    {
        Oferta::findOrFail($id)->delete();

        return back()->with('ok', 'Oferta eliminada.');
    }

    public function usuarios()
    {
        $usuarios = User::withCount(['anuncios', 'ofertas'])->latest()->paginate(20);

        return view('admin.usuarios', compact('usuarios'));
    }

    public function categorias()
    {
        $categorias = Categoria::withCount('anuncios')->orderBy('nombre')->get();

        return view('admin.categorias', compact('categorias'));
    }

    public function guardarCategoria(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'icono' => ['nullable', 'string', 'max:16'],
        ]);

        Categoria::create([
            'nombre' => $data['nombre'],
            'slug' => Str::slug($data['nombre']),
            'icono' => $data['icono'] ?? null,
        ]);

        return back()->with('ok', 'Categoría creada.');
    }

    public function eliminarCategoria(int $id)
    {
        Categoria::findOrFail($id)->delete();

        return back()->with('ok', 'Categoría eliminada.');
    }
}
