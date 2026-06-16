<?php

use App\Http\Controllers\BuscarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Endpoint htmx (devuelve el parcial de resultados)
Route::get('/buscar', BuscarController::class)->name('buscar');

// Detalle SSR, URL indexable
Route::get('/producto/{id}', [ProductoController::class, 'show'])
    ->whereNumber('id')
    ->name('producto.show');
