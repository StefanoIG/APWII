<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $usuario;  // Propiedad para almacenar los datos del usuario

    /**
     * Create a new message instance.
     *
     * @param $usuario
     */
    public function __construct($usuario)
    {
        $this->usuario = $usuario;  // Almacena el usuario que se va a registrar
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Registro Exitoso')
                    ->view('emails.welcome')
                    ->with([
                        'nombre' => $this->usuario->nombre,
                        'correo' => $this->usuario->correo_electronico
                    ]);
    }
}
