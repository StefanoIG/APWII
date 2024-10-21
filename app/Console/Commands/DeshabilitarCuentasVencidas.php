<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Factura;
use Carbon\Carbon;

class DeshabilitarCuentasVencidas extends Command
{
    // Nombre y descripción del comando
    protected $signature = 'facturas:deshabilitar-cuentas';
    protected $description = 'Deshabilitar cuentas de usuarios cuyas facturas están vencidas y no tienen una factura posterior';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Obtiene la fecha actual
        $hoy = Carbon::now();

        // Busca todas las facturas con proxima_fecha_pago y fecha_gracia en el pasado y que no estén pagadas
        $facturasVencidas = Factura::where('estado', 'pendiente')
            ->where(function($query) use ($hoy) {
                $query->where('proxima_fecha_pago', '<', $hoy)
                      ->orWhere('fecha_gracia', '<', $hoy);
            })
            ->get();

        foreach ($facturasVencidas as $factura) {
            $usuario = $factura->usuario;  // Relación usuario con facturas

            // Buscar si existe otra factura posterior del mismo usuario con fecha de pago o gracia futura
            $facturaPosterior = Factura::where('usuario_id', $usuario->id)
                ->where(function($query) use ($factura) {
                    $query->where('proxima_fecha_pago', '>', $factura->proxima_fecha_pago)
                          ->orWhere('fecha_gracia', '>', $factura->fecha_gracia);
                })
                ->where('estado', '!=', 'cancelado')
                ->first();

            // Si no hay facturas posteriores, deshabilitar la cuenta
            if (!$facturaPosterior) {
                $usuario->estado = "deshabilitado";  // Suponiendo que tienes un campo `habilitado` en tu tabla usuarios
                $usuario->save();

                // Loguear que se deshabilitó la cuenta
                $this->info('Cuenta del usuario ' . $usuario->name . ' deshabilitada por falta de pago.');
            }
        }

        $this->info('Proceso de deshabilitación de cuentas completado.');
    }
}
