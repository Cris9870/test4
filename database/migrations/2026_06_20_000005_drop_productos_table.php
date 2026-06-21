<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversa pivota de catálogo (Producto) a marketplace inversa (Anuncio/Oferta).
 * El server ya tiene la tabla 'productos' de un migrate previo: la dropeamos aquí
 * para que el `migrate --force` recurrente converja la BD viva sin tocar historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('productos');
    }

    public function down(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion');
            $table->string('categoria')->index();
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('imagen_url');
            $table->timestamps();
        });
    }
};
