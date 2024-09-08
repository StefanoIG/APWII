<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecoveryPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $Usuario;
    public $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($Usuario, $resetUrl)
    {
        $this->Usuario = $Usuario;
        $this->resetUrl = $resetUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Recuperación de contraseña')
                    ->view('emails.recuperarContraseña')
                    ->with([
                        'nombre' => $this->Usuario->name,
                        'resetUrl' => $this->resetUrl,
                    ]);
    }
}
