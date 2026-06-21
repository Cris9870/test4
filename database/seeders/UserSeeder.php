<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin general (password en claro -> el cast 'hashed' lo hashea una vez).
        User::updateOrCreate(
            ['email' => 'admin@reversa.pe'],
            ['name' => 'Admin Reversa', 'password' => 'password', 'es_admin' => true],
        );

        $usuarios = [
            ['Laura Vargas', 'laura@reversa.pe'],
            ['Andrés Pérez', 'andres@reversa.pe'],
            ['Mariana López', 'mariana@reversa.pe'],
            ['Sebastián Gómez', 'sebastian@reversa.pe'],
            ['Valentina Méndez', 'valentina@reversa.pe'],
        ];

        foreach ($usuarios as [$name, $email]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => 'password', 'es_admin' => false],
            );
        }
    }
}
