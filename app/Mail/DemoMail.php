<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DemoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $usuarioDemo;
    public $passwordPlano;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Usuario $usuarioDemo, $passwordPlano)
    {
        $this->usuarioDemo = $usuarioDemo;
        $this->passwordPlano = $passwordPlano;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Acceso a Demo Aprobado')
                    ->view('emails.demoMail');
    }
}
