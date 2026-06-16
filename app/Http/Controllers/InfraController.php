<?php

namespace App\Http\Controllers;

use App\Jobs\PingJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Endpoints de diagnostico de infraestructura (cola + scheduler).
 * Devuelven JSON para poder verificarlos por HTTP.
 */
class InfraController extends Controller
{
    /** GET /infra : estado actual (drivers + ultimas ejecuciones). */
    public function estado()
    {
        return response()->json([
            'now' => now()->toDateTimeString(),
            'queue_connection' => config('queue.default'),
            'cache_store' => config('cache.default'),
            'scheduler_last_run' => Cache::get('scheduler_last_run'),
            'queue_dispatched' => Cache::get('queue_dispatched'),
            'queue_last_job' => Cache::get('queue_last_job'),
        ]);
    }

    /** GET /infra/dispatch : encola un PingJob y devuelve al instante. */
    public function dispatchJob()
    {
        $token = (string) Str::uuid();

        Cache::put('queue_dispatched', [
            'token' => $token,
            'at' => now()->toDateTimeString(),
        ], 3600);

        PingJob::dispatch($token);

        return response()->json([
            'dispatched' => $token,
            'at' => now()->toDateTimeString(),
            'nota' => 'Si la cola es async, esta respuesta es instantanea y queue_last_job aparecera en ~3s.',
        ]);
    }
}
