<?php

namespace Database\Seeders;

use App\Models\Anuncio;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database (Reversa: categorías, usuarios, anuncios, ofertas).
     *
     * NO usamos WithoutModelEvents porque desactivaría el hook saving() del modelo
     * Anuncio (que rellena la columna normalizada 'busqueda'). En su lugar desactivamos
     * SOLO el indexado de Scout: el hook corre, pero no se toca Meilisearch durante el
     * seed. La indexación se hace aparte con `php artisan scout:import 'App\Models\Anuncio'`.
     */
    public function run(): void
    {
        Anuncio::disableSearchSyncing();

        $this->call([
            CategoriaSeeder::class,
            UserSeeder::class,
            AnuncioSeeder::class,
            OfertaSeeder::class,
        ]);
    }
}
