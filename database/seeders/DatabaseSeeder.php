<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Sin auth en este test: solo poblamos el catalogo.
     * La indexacion a Meilisearch se hace aparte con `php artisan scout:import`.
     */
    public function run(): void
    {
        $this->call(ProductoSeeder::class);
    }
}
