<?php

use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    app(\App\Http\Controllers\LoteController::class)->verificarLotesExpirados();
})->dailyAt('00:00');


// Ejecutar el comando para deshabilitar las cuentas demo que han expirado
Schedule::call(function () {
    app(\App\Console\Commands\DisableExpiredDemos::class)->handle();
})->dailyAt('00:00');

// $schedule->command('facturas:deshabilitar-cuentas')->dailyAt('00:00');
