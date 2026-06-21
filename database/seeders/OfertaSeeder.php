<?php

namespace Database\Seeders;

use App\Models\Anuncio;
use App\Models\Oferta;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfertaSeeder extends Seeder
{
    public function run(): void
    {
        $vendedores = User::where('es_admin', false)->orderBy('id')->get();

        foreach (Anuncio::orderBy('id')->get() as $i => $anuncio) {
            // Candidatos a vendedor = todos menos el comprador del anuncio.
            $cands = $vendedores->where('id', '!=', $anuncio->user_id)->values();
            if ($cands->isEmpty()) {
                continue;
            }

            if ($anuncio->estado === 'cerrado') {
                // 3 ofertas: la primera aceptada, las demás rechazadas.
                foreach ([0, 1, 2] as $k) {
                    $vendedor = $cands[$k % $cands->count()];
                    Oferta::updateOrCreate(
                        ['anuncio_id' => $anuncio->id, 'user_id' => $vendedor->id],
                        [
                            'precio' => max(1, (int) $anuncio->presupuesto - ($k * 10)),
                            'mensaje' => 'Yo tengo uno, en muy buen estado. Te lo puedo mostrar.',
                            'estado' => $k === 0 ? 'aceptada' : 'rechazada',
                        ],
                    );
                }

                continue;
            }

            // Abiertos: 0..3 ofertas pendientes (determinístico por índice).
            $n = $i % 4;
            for ($k = 0; $k < $n; $k++) {
                $vendedor = $cands[$k % $cands->count()];
                Oferta::updateOrCreate(
                    ['anuncio_id' => $anuncio->id, 'user_id' => $vendedor->id],
                    [
                        'precio' => max(1, (int) $anuncio->presupuesto + (($k - 1) * 15)),
                        'mensaje' => 'Yo tengo uno, casi nuevo. ¿Te interesa?',
                        'estado' => 'pendiente',
                    ],
                );
            }
        }
    }
}
