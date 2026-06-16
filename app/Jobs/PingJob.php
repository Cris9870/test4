<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Job de prueba para validar la COLA (worker del Laravel Toolkit).
 * Duerme unos segundos (para distinguir async/worker de sync/inline) y
 * deja constancia en cache de cuando lo proceso el worker.
 */
class PingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function handle(): void
    {
        // Simula trabajo: si fuera QUEUE=sync, la peticion de /infra/dispatch
        // se bloquearia estos segundos; con worker real, devuelve al instante.
        sleep(3);

        Cache::put('queue_last_job', [
            'token' => $this->token,
            'at' => now()->toDateTimeString(),
        ], 3600);
    }
}
