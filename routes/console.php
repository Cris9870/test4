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
