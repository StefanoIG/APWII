<?php

namespace App\Mail;

use App\Models\Demo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestDemoMail extends Mailable
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
        return $this->subject('Nueva Solicitud de Demo')
                    ->view('emails.requestDemo');
    }
}
