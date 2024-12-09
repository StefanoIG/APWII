<?php

namespace App\Mail;

use App\Models\Demo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DemoRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $demoRequest;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Demo $demoRequest)
    {
        $this->demoRequest = $demoRequest;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Solicitud de Demo Rechazada')
                    ->view('emails.demoRejectedMail');
    }
}
