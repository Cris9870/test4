<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tarea programada de PRUEBA: cada minuto deja un timestamp en cache.
// Sirve para validar que el Scheduler (toggle "Tareas programadas" del Laravel Toolkit) corre.
Schedule::call(function () {
    Cache::put('scheduler_last_run', now()->toDateTimeString(), 3600);
})->everyMinute()->name('infra-scheduler-ping')->withoutOverlapping();

// Worker de cola DRIVEN POR EL SCHEDULER (alternativa al toggle "Cola" del Toolkit).
// El scheduler -ya activo- ejecuta queue:work cada minuto: procesa lo encolado y sale.
// Latencia hasta ~1 min (vs. instantaneo del worker persistente del Toolkit), pero 100% fiable.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->name('infra-queue-worker');
