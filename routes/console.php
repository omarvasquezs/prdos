<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Actualizar Padrón RUC de SUNAT mensualmente (día 5, 03:00 AM)
Schedule::command('padron:importar')
    ->monthlyOn(5, '03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Padrón RUC actualizado automáticamente.');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Error en actualización automática del Padrón RUC.');
    });
