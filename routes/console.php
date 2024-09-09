<?php

use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    app(\App\Http\Controllers\LoteController::class)->verificarLotesExpirados();
})->hourly();
