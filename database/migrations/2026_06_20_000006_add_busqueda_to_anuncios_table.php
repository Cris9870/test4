<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columna 'busqueda' = texto normalizado (sin acentos, minúsculas) de
 * titulo+descripcion+categoria. Permite búsqueda insensible a tildes sin depender
 * de que Meilisearch pliegue diacríticos ni de la extensión unaccent de Postgres.
 * La rellena el hook saving() del modelo Anuncio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anuncios', function (Blueprint $table) {
            $table->text('busqueda')->nullable()->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('anuncios', function (Blueprint $table) {
            $table->dropColumn('busqueda');
        });
    }
};
