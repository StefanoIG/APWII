<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Demo;
use App\Models\Usuario;

class DisableExpiredDemos extends Command
{
    protected $signature = 'demos:disable-expired';
    protected $description = 'Deshabilitar cuentas demo que han expirado';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Obtener las demos que han expirado
        $expiredDemos = Demo::where('isActive', true)
            ->where('demo_expiry_date', '<=', now())
            ->get();

        foreach ($expiredDemos as $demo) {
            // Deshabilitar la cuenta en la tabla demo
            $demo->update([
                'isActive' => false, // Deshabilitar la cuenta
            ]);

            // Deshabilitar la cuenta en la tabla usuarios
            if ($demo->usuario_id) {
                $user = Usuario::find($demo->usuario_id);
                if ($user) {
                    $user->update([
                        'estado' => "deshabilitado", // Deshabilitar el usuario
                    ]);
                }
            }
        }

        $this->info('Las cuentas demo expiradas han sido deshabilitadas.');
    }
}
