<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnuncioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuscarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfraController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Endpoint htmx (devuelve el parcial de resultados)
Route::get('/buscar', BuscarController::class)->name('buscar');

// Listado pre-filtrado por categoría (reusa el muro de la home)
Route::get('/categoria/{slug}', [HomeController::class, 'categoria'])->name('categoria.show');

// Detalle SSR de una solicitud de compra (URL indexable)
Route::get('/anuncio/{id}', [AnuncioController::class, 'show'])
    ->whereNumber('id')
    ->name('anuncio.show');

// Diagnostico de infraestructura: cola + scheduler (JSON)
Route::get('/infra', [InfraController::class, 'estado'])->name('infra');
Route::get('/infra/dispatch', [InfraController::class, 'dispatchJob']);

// Prueba de subida de imagenes (disco public + symlink storage)
Route::get('/subir', [UploadController::class, 'form'])->name('subir');
Route::post('/subir', [UploadController::class, 'store']);
Route::get('/infra/upload-test', [UploadController::class, 'test']);

// Auth a medida (primitivas de Laravel, sin paquetes)
Route::middleware('guest')->group(function () {
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('registro');
    Route::post('/registro', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/cuenta', [AuthController::class, 'cuenta'])->name('cuenta');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Publicar una solicitud de compra
    Route::get('/publicar', [AnuncioController::class, 'create'])->name('publicar');
    Route::post('/publicar', [AnuncioController::class, 'store']);

    // Ofertas: un vendedor oferta; el comprador acepta
    Route::post('/anuncio/{id}/oferta', [OfertaController::class, 'store'])
        ->whereNumber('id')->name('oferta.store');
    Route::post('/oferta/{id}/aceptar', [OfertaController::class, 'aceptar'])
        ->whereNumber('id')->name('oferta.aceptar');
});

// Panel de administración (subcarpeta /admin, sin subdominio), solo es_admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/anuncios', [AdminController::class, 'anuncios'])->name('anuncios');
    Route::post('/anuncios/{id}/cerrar', [AdminController::class, 'cerrarAnuncio'])->whereNumber('id')->name('anuncios.cerrar');
    Route::delete('/anuncios/{id}', [AdminController::class, 'eliminarAnuncio'])->whereNumber('id')->name('anuncios.eliminar');

    Route::get('/ofertas', [AdminController::class, 'ofertas'])->name('ofertas');
    Route::delete('/ofertas/{id}', [AdminController::class, 'eliminarOferta'])->whereNumber('id')->name('ofertas.eliminar');

    Route::get('/usuarios', [AdminController::class, 'usuarios'])->name('usuarios');

    Route::get('/categorias', [AdminController::class, 'categorias'])->name('categorias');
    Route::post('/categorias', [AdminController::class, 'guardarCategoria'])->name('categorias.guardar');
    Route::delete('/categorias/{id}', [AdminController::class, 'eliminarCategoria'])->whereNumber('id')->name('categorias.eliminar');
});
