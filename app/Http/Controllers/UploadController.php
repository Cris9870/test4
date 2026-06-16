<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Prueba de subida de archivos al disco "public" (storage/app/public),
 * servido por el symlink public/storage (creado con: php artisan storage:link).
 */
class UploadController extends Controller
{
    /** GET /subir : formulario + galeria de lo subido. */
    public function form()
    {
        $imagenes = collect(Storage::disk('public')->files('uploads'))
            ->sortDesc()
            ->map(fn ($p) => Storage::disk('public')->url($p))
            ->values();

        return view('subir', ['imagenes' => $imagenes]);
    }

    /** POST /subir : valida y guarda la imagen en el disco public. */
    public function store(Request $request)
    {
        $request->validate([
            'imagen' => 'required|image|max:4096', // 4 MB
        ]);

        $path = $request->file('imagen')->store('uploads', 'public');

        return redirect()->route('subir')
            ->with('ok', 'Imagen subida: ' . Storage::disk('public')->url($path));
    }

    /**
     * GET /infra/upload-test : diagnostico (sin CSRF). Escribe un PNG 1x1 en el
     * disco public y devuelve su URL; si GET a esa URL da 200, el symlink funciona.
     */
    public function test()
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        );
        $name = 'uploads/diag-' . now()->format('YmdHis') . '.png';
        Storage::disk('public')->put($name, $png);

        return response()->json([
            'written' => Storage::disk('public')->exists($name),
            'url' => Storage::disk('public')->url($name),
            'public_storage_symlink' => is_link(public_path('storage')),
            'nota' => 'Si GET a "url" da 200 => storage:link + disco public OK.',
        ]);
    }
}
