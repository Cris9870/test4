<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            // El comprador que publica la búsqueda de compra.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // FK opcional a categorias (integridad/admin) + nombre denormalizado abajo.
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion');
            // 'categoria' DENORMALIZADO (string) para faceting Meili + groupBy del fallback PG.
            $table->string('categoria')->index();
            $table->decimal('presupuesto', 10, 2);
            $table->string('imagen_url')->nullable();
            $table->string('estado')->default('abierto')->index(); // abierto | cerrado
            $table->string('ciudad')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios');
    }
};
