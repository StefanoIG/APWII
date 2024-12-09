<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OwnerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $owner;

    public function __construct($owner)
    {
        $this->owner = $owner;
    }

    public function build()
    {
        return $this->subject('¡Bienvenido a InventoryPro!')
            ->view('emails.owner_mail');
    }
}
