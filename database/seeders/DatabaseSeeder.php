<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database (Reversa: categorías, usuarios, anuncios, ofertas).
     *
     * WithoutModelEvents evita indexar a Meilisearch durante el seed;
     * la indexación se hace aparte con `php artisan scout:import 'App\Models\Anuncio'`.
     */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            UserSeeder::class,
            AnuncioSeeder::class,
            OfertaSeeder::class,
        ]);
    }
}
