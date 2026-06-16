<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Comando de prueba para validar la config SMTP.
 * Uso (pestaña Artisan del Toolkit, SIN comillas):  mail:test tucorreo@ejemplo.com
 */
class TestMail extends Command
{
    protected $signature = 'mail:test {to}';

    protected $description = 'Envia un email de prueba para validar la config SMTP';

    public function handle(): int
    {
        $to = $this->argument('to');
        $this->info("Enviando email de prueba a {$to} ...");

        try {
            Mail::raw(
                'Email de prueba desde '.config('app.name').' ('.config('app.url').'). '
                .'Hora del servidor: '.now()->toDateTimeString(),
                function ($m) use ($to) {
                    $m->to($to)->subject('Prueba SMTP — '.config('app.name'));
                }
            );

            $this->info("OK: enviado a {$to} sin excepcion (el SMTP acepto el envio). Revisa la bandeja (y spam).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('FALLO SMTP: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
