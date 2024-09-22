<?php

use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    app(\App\Http\Controllers\LoteController::class)->verificarLotesExpirados();
})->hourly();


// Ejecutar el comando para deshabilitar las cuentas demo que han expirado
Schedule::call(function () {
    app(\App\Console\Commands\DisableExpiredDemos::class)->handle();
})->hourly();
